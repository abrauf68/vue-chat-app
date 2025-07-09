<?php

namespace App\Http\Controllers;

use App\Events\GroupMessageDelete;
use App\Events\GroupMessageHide;
use App\Events\GroupMessageReaction as EventsGroupMessageReaction;
use App\Events\GroupMessageSent;
use App\Events\GroupMessageUpdate;
use App\Events\MessageDelete;
use App\Events\MessageHide;
use App\Events\MessageSent;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
use App\Models\GroupMessageDeletion;
use App\Models\GroupMessageReaction;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class GroupMessageController extends Controller
{
    public function getGroupMessage($id)
    {
        $group = Group::findOrFail($id);
        if (!$group) {
            return response()->json(['error' => 'Group not found'], 404);
        }
        $currentUserId = auth()->id();
        if (!is_object($group)) {
            return response()->json(['error' => 'Invalid group'], 400);
        }
        $GroupMember = GroupMember::where('group_id', $group->id)
        ->where('user_id', $currentUserId)->first();

        $joinDateTime = \Carbon\Carbon::parse($GroupMember->created_at);

        $messages = GroupMessage::query()
            ->where('group_id', $group->id)
            ->where('created_at', '>=' ,$joinDateTime)
            ->whereDoesntHave('groupMessageDeletion', function ($query) use ($currentUserId) {
                $query->where('user_id', $currentUserId);
            })
            ->with(['sender', 'repliedTo', 'groupMessageReaction'])
            ->orderBy('created_at', 'asc')
            ->get();

        return $messages;
    }

    public function sendGroupMessage(Request $request, $groupId)
    {
        $request->validate([
            'message' => 'nullable|string',
            'file' => 'nullable|file',
            'audio' => 'nullable',
            'replied_to' => 'nullable|exists:group_messages,id',
        ]);

        $messageData = [
            'sender_id' => auth()->id(),
            'group_id' => $groupId,
            'text' => $request->input('message'),
            'status' => 'sent',
            'replied_to' => $request->input('replied_to') ?? null,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $file->move(public_path('uploads'), $filename);
            $messageData['attachment'] = 'uploads/' . $filename;

            $filePath = public_path('uploads/' . $filename);
            $fileSize = filesize($filePath);

            $messageData['attachment_size'] = $fileSize;
        }

        if ($request->hasFile('audio')) {
            $audio = $request->file('audio');
            $audioFilename = time() . '_audio' . $audio->getClientOriginalName();
            $audio->move(public_path('uploads'), $audioFilename);
            $messageData['attachment'] = 'uploads/' . $audioFilename;
    
            $audioPath = public_path('uploads/' . $audioFilename);
            $audioSize = filesize($audioPath);
            $messageData['attachment_size'] = $audioSize;
        }

        $message = GroupMessage::create($messageData);

        $removedMembers = GroupMember::where('group_id', $groupId)
        ->where('is_removed', '1')
        ->pluck('user_id');

        foreach ($removedMembers as $userId) {
            $message_deletion = new GroupMessageDeletion();
            $message_deletion->group_message_id = $message->id;
            $message_deletion->user_id = $userId;
            $message_deletion->save();
            broadcast(new GroupMessageHide($message));
        }

        $message->load(['sender', 'repliedTo']);

        broadcast(new GroupMessageSent($message));

        return response()->json($message);
    }

    public function destroy($id)
    {
        $message = GroupMessage::findOrFail($id);
        if ($message) {
            $message->is_deleted = 1;
            $message->save();

            broadcast(new GroupMessageDelete($message));
            return response()->json(['message' => 'Message deleted successfully.'], 200);
        }
        return response()->json(['message' => 'Message not found.'], 404);
    }

    public function hideGroupMessage($id)
    {
        try {
            $message = GroupMessage::findOrFail($id);
            
            $messageDeletion = new GroupMessageDeletion();
            $messageDeletion->group_message_id = $message->id;
            $messageDeletion->user_id = auth()->id();
            $messageDeletion->save();

            // Load relationships
            // $message->load(['sender', 'receiver', 'repliedTo', 'groupMessageDeletion']);
            
            // Broadcast the deletion
            broadcast(new GroupMessageHide($message));
            
            return response()->json(['message' => 'Message deleted successfully.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Message not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while deleting the message.'], 500);
        }
    }

    public function hideAllGroupMessage(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        $userId = auth()->id();
        $messages = GroupMessage::query()
        ->where(function ($query) use ($group) {
            $query->where('group_id', $group->id);
        })
        ->whereDoesntHave('groupMessageDeletion', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->with(['sender', 'repliedTo'])
        ->orderBy('created_at', 'asc')
        ->get();

        // dd($messages);
        if ($messages) {
            foreach ($messages as $message) {
                $message_deletion = New GroupMessageDeletion();
                $message_deletion->group_message_id = $message->id;
                $message_deletion->user_id = auth()->id();
                $message_deletion->save();
                broadcast(new GroupMessageHide($message));
            }
            return response()->json(['message' => 'Messages deleted successfully.'], 200);
        }
        return response()->json(['message' => 'Message not found.'], 404);
    }

    public function hideMultipleGroupMessage(Request $request)
    {
        $validated = $request->validate([
            'messageIds' => 'required|array',
            'messageIds.*' => 'integer|exists:group_messages,id', // Validate that each ID exists
        ]);

        try {
            foreach ($validated['messageIds'] as $messageId) {
                $message = GroupMessage::findOrFail($messageId); // This will throw a ModelNotFoundException if not found
                
                $message_deletion = new GroupMessageDeletion();
                $message_deletion->group_message_id = $message->id;
                $message_deletion->user_id = auth()->id();
                $message_deletion->save();
                broadcast(new GroupMessageHide($message));
            }

            return response()->json(['message' => 'Messages deleted successfully.'], 200);
        } catch (\Exception $e) {
            // Log the exception for further analysis
            \Log::error('Error deleting messages: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);
        $message = GroupMessage::findOrFail($id);
        $message->text = $request->input('message');
        $message->save();

        broadcast(new GroupMessageUpdate($message));
        return response()->json($message);
    }

    public function forwardGroupMessage(Request $request)
    {
        $request->validate([
            'message_id' => 'required|exists:group_messages,id',
            'recipient_id' => 'required|exists:users,id',
        ]);

        $originalMessage = GroupMessage::find($request->message_id);

        $messageData = [
            'sender_id' => auth()->id(),
            'receiver_id' => $request->recipient_id,
            'text' => $originalMessage->text,
            'attachment' => $originalMessage->attachment,
            'status' => 'sent',
            'replied_to' => null, // Set to null for forwarded messages
            'is_forwarded' => 1, // Mark as forwarded
        ];

        if ($originalMessage->attachment) {
            $filePath = public_path($originalMessage->attachment);
            if (file_exists($filePath)) {
                $messageData['attachment_size'] = filesize($filePath);
            }
        }

        $newMessage = Message::create($messageData);

        $newMessage->load(['sender', 'receiver', 'repliedTo']);

        broadcast(new MessageSent($newMessage));

        return response()->json([
            'redirect' => route('chat', $request->recipient_id),
            'message' => $newMessage,
        ], 201);
    }

    public function forwardGroupMessageToGroup(Request $request)
    {
        $request->validate([
            'message_id' => 'required|exists:group_messages,id',
            'recipient_id' => 'required|exists:groups,id',
        ]);

        $originalMessage = GroupMessage::find($request->message_id);

        $messageData = [
            'sender_id' => auth()->id(),
            'group_id' => $request->recipient_id,
            'text' => $originalMessage->text,
            'attachment' => $originalMessage->attachment,
            'status' => 'sent',
            'replied_to' => null, // Set to null for forwarded messages
            'is_forwarded' => 1, // Mark as forwarded
        ];

        if ($originalMessage->attachment) {
            $filePath = public_path($originalMessage->attachment);
            if (file_exists($filePath)) {
                $messageData['attachment_size'] = filesize($filePath);
            }
        }

        $newMessage = GroupMessage::create($messageData);

        $newMessage->load(['sender', 'repliedTo']);

        broadcast(new GroupMessageSent($newMessage));

        return response()->json([
            'redirect' => route('group-chat', $request->recipient_id),
            'message' => $newMessage,
        ], 201);
    }

    public function groupMessageReaction(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'group_message_id' => 'required|exists:group_messages,id',
            'emoji' => 'required'
        ]);
        $reaction = GroupMessageReaction::where('group_message_id', $request->group_message_id)
                                        ->where('user_id', $user->id)
                                        ->first();

        if ($reaction) {
            $reaction->emoji = $request->emoji;
            $reaction->save();
        } else {
            $reaction = GroupMessageReaction::create([
                'group_message_id' => $request->group_message_id,
                'user_id' => $user->id,
                'emoji' => $request->emoji
            ]);
        }
        $message = GroupMessage::with('groupMessageReaction')->findOrFail($request->group_message_id);
        broadcast(new EventsGroupMessageReaction($message))->toOthers();
        return response()->json([
            'success' => true,
            'reaction' => $reaction
        ]);
    }

    public function deletegroupMessageReaction($id)
    {
        $reaction = GroupMessageReaction::findOrFail($id);
        $message = GroupMessage::with('groupMessageReaction')->findOrFail($reaction->group_message_id);
        $reaction->delete();

        broadcast(new EventsGroupMessageReaction($message))->toOthers();

        return response()->json(['success' => true]);
    }

}
