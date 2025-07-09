<?php

namespace App\Http\Controllers;

use App\Events\AddGroupMember;
use App\Events\GroupMessageSent;
use App\Events\GroupUpdate;
use App\Events\MessageSent;
use App\Events\RemoveGroupMember;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    public function index($id)
    {
        $groups = Group::where('created_by', $id)->get();
        return view('groups',compact('groups'));
    }

    public function getGroups($id)
    {
        $createdGroups = Group::where('created_by', $id)->get();

        $memberGroups = Group::whereHas('members', function ($query) use ($id) {
            $query->where('user_id', $id);
        })->get();

        $groups = $createdGroups->merge($memberGroups)->unique('id');

        return response()->json($groups);
    }

    public function getUsers($groupId)
    {
        $existingMemberIds = GroupMember::where('group_id', $groupId)->where('is_removed', '0')->pluck('user_id');

        $users = User::whereNotIn('id', $existingMemberIds)
                    ->where('id', '!=', Auth::user()->id)
                    ->get();

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_private' => 'nullable|in:0,1',
        ]);

        try {
            $group = Group::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'is_private' => $request->input('is_private', '0'),
                'created_by' => auth()->id(),
            ]);

            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Group created successfully!',
                'data' => $group,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_private' => 'nullable|in:0,1',
        ]);

        try {
            $group = Group::findOrFail($id);
            
            // Store original values
            $originalName = $group->name;
            $originalDescription = $group->description;
            $originalPrivacy = $group->is_private;

            // Update group attributes
            $group->update([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'is_private' => $request->input('is_private'),
            ]);

            // Check for changes and create messages accordingly
            if ($request->input('name') !== $originalName) {
                $message = GroupMessage::create([
                    'group_id' => $id,
                    'sender_id' => '0',
                    'text' => 'Admin has changed group name to ' . $request->input('name'),
                ]);
                broadcast(new GroupMessageSent($message));
            }

            if ($request->input('description') !== $originalDescription) {
                $message = GroupMessage::create([
                    'group_id' => $id,
                    'sender_id' => '0',
                    'text' => 'Admin has changed the group description',
                ]);
                broadcast(new GroupMessageSent($message));
            }

            if ($request->input('is_private') !== $originalPrivacy) {
                $privacyText = $request->input('is_private') == '0' ? 'Public' : 'Private';
                $message = GroupMessage::create([
                    'group_id' => $id,
                    'sender_id' => '0',
                    'text' => 'Admin has changed group privacy to ' . $privacyText,
                ]);
                broadcast(new GroupMessageSent($message));
            }

            // Broadcast the group update event to others
            broadcast(new GroupUpdate($group))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Group updated successfully!',
                'data' => $group,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function addMembers(Request $request, $id)
    {
        $request->validate([
            'users' => 'required|array',
            'users.*' => 'exists:users,id',
        ]);

        try {
            foreach ($request->input('users') as $userId) {
                $existingMember = GroupMember::where('group_id', $id)
                    ->where('user_id', $userId)
                    ->first();
                
                $user = User::find($userId);

                if ($existingMember) {
                    if ($existingMember->is_removed == '1') {
                        $existingMember->is_removed = '0';
                        $existingMember->save();

                        $message = GroupMessage::create([
                            'group_id' => $id,
                            'sender_id' => '0',
                            'text' => $user->name.' has been added to the group by admin',
                        ]);
                    }
                } else {
                    GroupMember::create([
                        'group_id' => $id,
                        'user_id' => $userId,
                        'is_removed' => '0',
                    ]);

                    $message = GroupMessage::create([
                        'group_id' => $id,
                        'sender_id' => '0',
                        'text' => $user->name.' has been added to the group by admin',
                    ]);
                }

                broadcast(new GroupMessageSent($message));
                broadcast(new AddGroupMember($id, $userId));
            }

            return response()->json([
                'success' => true,
                'message' => 'Members added or restored successfully.',
                'data' => [
                    'group_id' => $id,
                    'added_users' => $request->input('users'),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add members.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function removeMember($id, $userId)
    {
        $groupMember = GroupMember::where('group_id', $id)
               ->where('user_id', $userId)
               ->first();
        $user = User::find($userId);
        if ($groupMember) {
            $groupMember->is_removed = 1;
            $groupMember->save();

            $message = GroupMessage::create([
                'group_id' => $id,
                'sender_id' => '0',
                'text' => $user->name.' has been removed from the group by admin',
            ]);
            broadcast(new GroupMessageSent($message));
            broadcast(new RemoveGroupMember($id, $userId));
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Member not found.',
            ], 404);
        }
    }

    public function groupChat($id)
    {
        $group = Group::findOrFail($id);
        $groupMembers = GroupMember::with('user')
            ->where('group_id', $id)
            ->get();

        $users = $groupMembers->map(function ($groupMember) {
            $user = $groupMember->user;
            $user->is_removed = $groupMember->is_removed;
            return $user;
        });

        return view('group-chat', compact('group', 'users'));
    }

    public function destroy($id)
    {
        $group = Group::findOrFail($id);

        GroupMember::where('group_id', $id)->delete();

        GroupMessage::where('group_id', $id)->delete();

        $group->delete();

        return redirect()->back()->with('success', 'Group deleted successfully.');
    }
}
