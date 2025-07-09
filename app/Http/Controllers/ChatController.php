<?php

namespace App\Http\Controllers;

use App\Events\GroupMessageSent;
use App\Events\MessageDelete;
use App\Events\MessageDelivered;
use App\Events\MessageHide;
use App\Events\MessageReaction as EventsMessageReaction;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\MessageUpdate;
use App\Events\UserOnline;
use App\Models\GroupMessage;
use App\Models\Message;
use App\Models\MessageDeletion;
use App\Models\MessageReaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function chat(User $user)
    {
        return view('chat', compact('user'));
    }
    public function getMessage($id)
    {
        $user = User::findOrFail($id);
        $messages = Message::query()
            ->where(function ($query) use ($user) {
                $query->where('sender_id', auth()->id())
                    ->where('receiver_id', $user->id)
                    ->where('is_deleted', '0')
                    ->whereDoesntHave('messageDeletion', function ($query) {
                        $query->where('user_id', auth()->id());  // Exclude deleted messages
                    });
            })
            ->orWhere(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->where('receiver_id', auth()->id())
                    ->whereDoesntHave('messageDeletion', function ($query) {
                        $query->where('user_id', auth()->id());  // Exclude deleted messages
                    });
            })
            ->with(['sender', 'receiver', 'repliedTo', 'messageReaction'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Update message status to 'delivered' when the recipient fetches the messages
        foreach ($messages as $message) {
            if ($message->receiver_id == auth()->id() && $message->status === 'sent') {
                $message->status = 'delivered';
                $message->save();

                broadcast(new MessageDelivered($message))->toOthers();
            }
        }

        return $messages;
    }

    public function getAllUsers()
    {
        $users = User::where('id', '!=', auth()->id())->get(['id', 'name']);
        return response()->json($users);
    }

    public function sendMessage(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'message' => 'nullable|string',
            'file' => 'nullable|file',
            'audio' => 'nullable',
            'replied_to' => 'nullable|exists:messages,id', 
        ]);
        // Store the message in the database
        $messageData = [
            'sender_id' => auth()->id(),
            'receiver_id' => $user->id,
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

        $message = Message::create($messageData);

        $message->load(['sender', 'receiver', 'repliedTo' ,'messageDeletion']);

        broadcast(new MessageSent($message));

        return response()->json($message);
    }

    public function destroy($id)
    {
        $message = Message::findOrFail($id);
        if ($message) {
            $message->is_deleted = 1;
            $message->save();
            
            broadcast(new MessageDelete($message));
            return response()->json(['message' => 'Message deleted successfully.'], 200);
        }
        return response()->json(['message' => 'Message not found.'], 404);
    }

    public function hideMessage($id)
    {
        $message = Message::findOrFail($id);
        if ($message) {
            $message_deletion = New MessageDeletion();
            $message_deletion->message_id = $message->id;
            $message_deletion->user_id = auth()->id();
            $message_deletion->save();

            $message->load(['sender', 'receiver', 'repliedTo', 'messageDeletion']);
            broadcast(new MessageHide($message));
            return response()->json(['message' => 'Message deleted successfully.'], 200);
        }
        return response()->json(['message' => 'Message not found.'], 404);
    }

    public function hideAllMessages(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $messages = Message::query()
            ->where(function ($query) use ($user) {
                $query->where('sender_id', auth()->id())
                    ->where('receiver_id', $user->id)
                    ->whereDoesntHave('messageDeletion', function ($query) {
                        $query->where('user_id', auth()->id());  // Exclude deleted messages
                    });
            })
            ->orWhere(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->where('receiver_id', auth()->id())
                    ->whereDoesntHave('messageDeletion', function ($query) {
                        $query->where('user_id', auth()->id());  // Exclude deleted messages
                    });
            })
            ->get();
            
        // dd($messages);
        if ($messages) {
            foreach ($messages as $message) {
                $message_deletion = New MessageDeletion();
                $message_deletion->message_id = $message->id;
                $message_deletion->user_id = auth()->id();
                $message_deletion->save();
                broadcast(new MessageHide($message));
            }
            return response()->json(['message' => 'Messages deleted successfully.'], 200);
        }
        return response()->json(['message' => 'Message not found.'], 404);
    }

    public function hideMultipleMessage(Request $request)
    {
        foreach($request->messageIds as $messageId){
            $message = Message::findOrFail($messageId);
            if ($message) {
                $message_deletion = New MessageDeletion();
                $message_deletion->message_id = $message->id;
                $message_deletion->user_id = auth()->id();
                $message_deletion->save();
                broadcast(new MessageHide($message));
            }else{
                return response()->json(['message' => 'Message not found.'], 404);
            }
        }
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);
        $message = Message::findOrFail($id);
        $message->text = $request->input('message');
        $message->save();

        broadcast(new MessageUpdate($message));
        return response()->json($message);
    }

    public function markAsRead($id)
    {
        $message = Message::findOrFail($id);

        // Check if the message belongs to the current user and the message status is 'delivered'
        if ($message->receiver_id == auth()->id() && $message->status === 'delivered') {
            $message->status = 'read';
            $message->save();

            // Broadcast to other users that the message has been read
            broadcast(new MessageRead($message));
        }

        return response()->json(['message' => 'Message marked as read successfully.'], 200);
    }

    public function forwardMessage(Request $request)
    {
        $request->validate([
            'message_id' => 'required|exists:messages,id',
            'recipient_id' => 'required|exists:users,id',
        ]);

        $originalMessage = Message::find($request->message_id);

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

    public function forwardMessageToGroup(Request $request)
    {
        $request->validate([
            'message_id' => 'required|exists:messages,id',
            'recipient_id' => 'required|exists:groups,id',
        ]);

        $originalMessage = Message::find($request->message_id);

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

    public function messageReaction(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'message_id' => 'required|exists:messages,id',
            'emoji' => 'required'
        ]);
        $reaction = MessageReaction::where('message_id', $request->message_id)
                                ->where('user_id', $user->id)
                                ->first();

        if ($reaction) {
            $reaction->emoji = $request->emoji;
            $reaction->save();
        } else {
            $reaction = MessageReaction::create([
                'message_id' => $request->message_id,
                'user_id' => $user->id,
                'emoji' => $request->emoji
            ]);
        }
        $message = Message::with('messageReaction')->findOrFail($request->message_id);
        broadcast(new EventsMessageReaction($message))->toOthers();
        return response()->json([
            'success' => true,
            'reaction' => $reaction
        ]);
    }

    public function deleteReaction($id)
    {
        $reaction = MessageReaction::findOrFail($id);
        $message = Message::with('messageReaction')->findOrFail($reaction->message_id);
        $reaction->delete();

        broadcast(new EventsMessageReaction($message))->toOthers();

        return response()->json(['success' => true]);
    }

    public function fetchLinkPreview(Request $request)
    {
        $url = $request->query('url');

        if (!$url) {
            return response()->json(['error' => 'Invalid URL'], 400);
        }

        $response = Http::get($url);
        $html = $response->body();

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        $titleTags = $dom->getElementsByTagName('title');
        $title = $titleTags->length > 0 ? $titleTags->item(0)->textContent : 'No title found';

        $descriptionTag = $xpath->query('//meta[@name="description"]/@content | //meta[@property="og:description"]/@content');
        $description = $descriptionTag->length > 0 ? $descriptionTag->item(0)->value : 'No description found';

        $imageTag = $xpath->query('//meta[@property="og:image"]/@content | //meta[@itemprop="image primaryImageOfPage"]/@content');
        $image = $imageTag->length > 0 ? $imageTag->item(0)->value : null;

        return response()->json([
            'title' => $title,
            'description' => $description,
            'image' => $image,
        ]);
    }
}
