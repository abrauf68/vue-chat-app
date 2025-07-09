<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMessageController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();
Route::middleware(['auth'])->group(function () {
    Route::post('/messages/forward', [ChatController::class, 'forwardMessage']);
    Route::post('/messages/forward-group', [ChatController::class, 'forwardMessageToGroup']);
    Route::post('/group/messages/forward', [GroupMessageController::class, 'forwardGroupMessage']);
    Route::post('/group/messages/forward-group', [GroupMessageController::class, 'forwardGroupMessageToGroup']);
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/chat/{user}',[ChatController::class,'chat'])->name('chat');
    Route::get('/messages/{id}',[ChatController::class,'getMessage'])->name('getMessage');
    Route::post('messages/{user}',[ChatController::class,'sendMessage']);
    Route::put('/messages/edit/{id}', [ChatController::class, 'update'])->name('messages.update');
    Route::put('/messages/delete/{id}', [ChatController::class, 'destroy']);
    Route::post('/messages/{id}/read', [ChatController::class, 'markAsRead']);
    Route::get('/users', [ChatController::class, 'getAllUsers']);
    Route::post('/messages/hide/{id}', [ChatController::class, 'hideMessage']);
    Route::post('/messages/clear/{id}', [ChatController::class, 'hideAllMessages']);
    Route::delete('/delete/multiple/messages', [ChatController::class, 'hideMultipleMessage']);
    Route::post('/messages/reactions/{id}', [ChatController::class, 'messageReaction']);
    Route::delete('/messages/reactions/{id}', [ChatController::class, 'deleteReaction']);
    Route::get('/link-preview', [ChatController::class, 'fetchLinkPreview']);

    Route::get('/groups/{id}', [GroupController::class, 'index'])->name('group');
    Route::get('/get-groups/{id}', [GroupController::class, 'getGroups']);
    Route::post('/groups', [GroupController::class, 'store'])->name('group.store');
    Route::put('/group/edit/{id}', [GroupController::class, 'update']);
    Route::get('/get-users/{id}', [GroupController::class, 'getUsers']);
    Route::post('/groups/{group}/add-members', [GroupController::class, 'addMembers']);
    Route::get('/group/chat/{id}', [GroupController::class, 'groupChat'])->name('group-chat');
    Route::get('/group/delete/{id}', [GroupController::class, 'destroy']);
    Route::delete('/remove/group/{id}/member/{userId}', [GroupController::class, 'removeMember']);

    Route::get('/group/{id}/messages',[GroupMessageController::class,'getGroupMessage'])->name('getGroupMessage');
    Route::post('group/{id}/messages',[GroupMessageController::class,'sendGroupMessage']);
    Route::put('/edit/group/{id}/messages', [GroupMessageController::class, 'update'])->name('groupMessages.update');
    Route::put('/delete/group/{Id}/messages', [GroupMessageController::class, 'destroy']);
    Route::post('/hide/group/{id}/messages', [GroupMessageController::class, 'hideGroupMessage']);
    Route::post('/group/messages/clear/{id}', [GroupMessageController::class, 'hideAllGroupMessage']);
    Route::post('/delete/multiple/group/messages', [GroupMessageController::class, 'hideMultipleGroupMessage']);
    Route::post('/group/messages/reactions/{id}', [GroupMessageController::class, 'groupMessageReaction']);
    Route::delete('/group/messages/reactions/{id}', [GroupMessageController::class, 'deletegroupMessageReaction']);

    // Route::post('/messages/{id}/read', [GroupMessageController::class, 'markAsRead']);
});
