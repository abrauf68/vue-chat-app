<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header" v-if="!isSelectingMessages">
                        {{ groupData.name }} <small>({{ groupData.description }})</small>
                        <button v-if="currentUser.id == group.created_by" class="btn btn-primary btn-sm float-end" @click="openEditModal">
                            Edit Group
                        </button>
                        <a class="btn btn-danger btn-sm float-end mx-2 rounded" @click="clearChat">Clear Chat</a>
                    </div>
                    <div class="card-header" v-if="isSelectingMessages">
                        <button class="btn float-end" @click="closeSeletedMessages"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="card-body chat-body" ref="messageContainer">
                        <div class="p-3 mb-3 text-center">
                            <div class="system-message">
                                <span>
                                    Created at <strong>{{ formatDate(group.created_at) }}</strong>
                                    by <strong>{{ createdByUser }}</strong>
                                </span>
                            </div>
                        </div>
                        <div class="d-flex flex-column" v-for="(messages, date) in groupedMessages" :key="date">
                            <div class="date-separator my-3 d-flex align-items-center">
                                <hr class="flex-grow-1" />
                                <h5 class="date-header text-center mx-3 small text-muted">{{ date }}</h5>
                                <hr class="flex-grow-1" />
                            </div>
                            <div v-for="message in messages" :key="message.id" 
                                class="d-flex mb-1"
                                :class="{
                                    'align-self-end': message.sender_id === currentUser.id,
                                    'align-self-center': message.sender_id === '0',
                                    // 'align-self-start': message.sender_id !== currentUser.id && message.sender_id !== '0'
                                }">
                                <div v-if="isSelectingMessages">
                                    <div class="form-check d-inline-block">
                                        <input 
                                            type="checkbox" 
                                            class="form-check-input rounded-circle" 
                                            :value="message.id" 
                                            v-model="selectedMessages" 
                                            id="messageCheckbox{{message.id}}" 
                                            @change="toggleFooter"
                                        />
                                        <label class="form-check-label" :for="'messageCheckbox' + message.id"></label>
                                    </div>
                                </div>
                                <div v-if="message.sender_id == '0'" class="system-message mx-auto text-center w-75">
                                    <span class="font-weight-bold text-muted">{{ message.text }}</span>
                                </div>
                                <div v-else>
                                    <div class="position-relative">
                                        <div v-if="message.is_deleted==1" class="text-muted small" style="font-style: italic;">
                                            This message is deleted by {{ getMessageSenderName(message.sender_id) }}
                                            <div class="dropdown position-absolute top-0 end-0 p-1">
                                                <button class="btn btn-link p-0" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa fa-ellipsis-v text-primary"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                                    <li><a class="dropdown-item" href="#" @click.prevent="hideMessage(message.id)">Delete</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div v-else>
                                            <div v-if="message.is_forwarded==1" class="text-muted small" style="font-style: italic;">
                                                Forwarded
                                            </div>
                                            <div v-if="message.replied_to" class="replied-message-container mb-2">
                                                <div v-if="message.replied_to.is_deleted==0" class="replied-message-content p-2">
                                                    <strong class="replied-message-sender">{{ getMessageSenderName(message.replied_to.sender_id) }}:</strong>
                                                    <span v-if="message.replied_to.text" class="replied-message-text">{{ message.replied_to.text }}</span>
                                                    <div v-else="message.replied_to.attachment" class="file-download">
                                                        <div v-if="isImage(message.replied_to.attachment)">
                                                            <a :href="`/${message.replied_to.attachment}`" :download="message.attachment">
                                                                <img :src="`/${message.replied_to.attachment}`" alt="" class="img-thumbnail">
                                                            </a>
                                                        </div>
                                                        <div v-else-if="isPDF(message.replied_to.attachment)">
                                                            <a :href="`/${message.replied_to.attachment}`" target="_blank" class="btn btn-link">
                                                                <iframe :src="`/${message.replied_to.attachment}`" width="250" height="250"></iframe>
                                                            </a>
                                                        </div>
                                                        <div v-else-if="isVideo(message.replied_to.attachment)">
                                                            <a :href="`/${message.replied_to.attachment}`" target="_blank" class="btn btn-link">
                                                                <video :src="`/${message.replied_to.attachment}`" class="video-thumbnail" controls></video>
                                                            </a>
                                                        </div>
                                                        <div v-else-if="isAudio(message.replied_to.attachment)" style="position: relative; display: inline-block;">
                                                            <audio 
                                                                :ref="el => audioRefs.set(message.id, el)"
                                                                :src="`/${message.replied_to.attachment}`" 
                                                                :class="message.replied_to.sender_id === currentUser.id ? 'audio-thumbnail' : ''" 
                                                                controls
                                                                controlsList="nodownload noremoteplayback"
                                                            ></audio>
                                                            <span 
                                                                class="btn"
                                                                :class="message.replied_to.sender_id === currentUser.id ? 'playSpeed' : 'playSpeed-receiver'"
                                                                @click="changeSpeed(message.replied_to.id)"
                                                            >
                                                                {{ getCurrentSpeed(message.replied_to.id) }}x
                                                            </span>
                                                        </div>
                                                        <div v-else>
                                                            <a :href="`/${message.replied_to.attachment}`" target="_blank" class="btn btn-link" style="text-decoration: none;">
                                                                <div class="card" :class="message.replied_to.sender_id === currentUser.id ? 'file-card' : 'file-card-receiver'">
                                                                    <div class="card-body">
                                                                        <div class="row">
                                                                            <div class="col-md-12 mb-3">
                                                                                <i class="fa fa-file"  style="font-size: 55px;" :class="message.replied_to.sender_id === currentUser.id ? 'file-card-icons' : 'file-card-icons-receiver'"></i>
                                                                            </div>
                                                                            <div class="col-md-12">
                                                                                <div class="row">
                                                                                    <div class="col-md-2" >
                                                                                        <i class="fa fa-file mt-2" style="font-size: 30px;" :class="message.replied_to.sender_id === currentUser.id ? 'file-card-icons' : 'file-card-icons-receiver'"></i>
                                                                                    </div>
                                                                                    <div class="col-md-10" style="text-align: left;">
                                                                                        <span class="card-title" style="font-size: 12px; font-weight: 700;">{{ message.replied_to.attachment.replace('uploads/', '') }}</span><br>
                                                                                        <span class="card-text" style="font-size: 12px;">{{ formatFileSize(message.replied_to.attachment_size) }} | File</span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else class="replied-message-content p-2">
                                                    <div class="text-muted small" style="font-style: italic;">
                                                        This message has been deleted!
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="editingMessageId === message.id">
                                                <input v-model="editedMessageText" type="text" class="form-control" />
                                                <button class="btn btn-success mt-2" @click="updateMessage(message.id)">Update</button>
                                                <button class="btn btn-secondary mt-2 ms-2" @click="cancelEdit">Cancel</button>
                                            </div>
                                            <div v-else>
                                                <div v-if="message.attachment" class="file-download">
                                                    <div v-if="isImage(message.attachment)">
                                                        <a :href="`/${message.attachment}`" :download="message.attachment">
                                                            <img :src="`/${message.attachment}`" alt="" class="img-thumbnail">
                                                        </a>
                                                    </div>
                                                    <div v-else-if="isPDF(message.attachment)">
                                                        <a :href="`/${message.attachment}`" target="_blank" class="btn btn-link">
                                                            <iframe :src="`/${message.attachment}`" width="250" height="250"></iframe>
                                                        </a>
                                                    </div>
                                                    <div v-else-if="isVideo(message.attachment)">
                                                        <a :href="`/${message.attachment}`" target="_blank" class="btn btn-link">
                                                            <video :src="`/${message.attachment}`" class="video-thumbnail" controls></video>
                                                        </a>
                                                    </div>
                                                    <div v-else-if="isAudio(message.attachment)" style="position: relative; display: inline-block;">
                                                        <audio 
                                                            :ref="el => audioRefs.set(message.id, el)"
                                                            :src="`/${message.attachment}`" 
                                                            :class="message.sender_id === currentUser.id ? 'audio-thumbnail' : ''" 
                                                            controls
                                                            controlsList="nodownload noremoteplayback"
                                                        ></audio>
                                                        <span 
                                                            class="btn"
                                                            :class="message.sender_id === currentUser.id ? 'playSpeed' : 'playSpeed-receiver'"
                                                            @click="changeSpeed(message.id)"
                                                        >
                                                            {{ getCurrentSpeed(message.id) }}x
                                                        </span>
                                                    </div>
                                                    <div v-else>
                                                        <a :href="`/${message.attachment}`" target="_blank" class="btn btn-link" style="text-decoration: none;">
                                                            <div class="card" :class="message.sender_id === currentUser.id ? 'file-card' : 'file-card-receiver'">
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-12 mb-3">
                                                                            <i class="fa fa-file"  style="font-size: 55px;" :class="message.sender_id === currentUser.id ? 'file-card-icons' : 'file-card-icons-receiver'"></i>
                                                                        </div>
                                                                        <div class="col-md-12">
                                                                            <div class="row">
                                                                                <div class="col-md-2" >
                                                                                    <i class="fa fa-file mt-2" style="font-size: 30px;" :class="message.sender_id === currentUser.id ? 'file-card-icons' : 'file-card-icons-receiver'"></i>
                                                                                </div>
                                                                                <div class="col-md-10" style="text-align: left;">
                                                                                    <span class="card-title" style="font-size: 12px; font-weight: 700;">{{ message.attachment.replace('uploads/', '') }}</span><br>
                                                                                    <span class="card-text" style="font-size: 12px;">{{ formatFileSize(message.attachment_size) }} | File</span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </a>
                                                    </div>
                                                    <div class="message-reactions">
                                                        <span 
                                                            v-for="reaction in message.group_message_reaction" 
                                                            :key="reaction.id" 
                                                            class="reaction-emoji float-end"
                                                            @click="reaction.user_id === props.currentUser.id ? removeReaction(reaction) : null"
                                                        >
                                                            {{ reaction.emoji }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div v-if="message.text && message.is_deleted==0" :class="message.sender_id === currentUser.id ? 'text-white message-bubble-out' : 'bg-light message-bubble-in'" class="px-3 py-1 border rounded">
                                                    <div v-if="isLink(message.text)">
                                                        <div v-if="linkPreviews[message.id]">
                                                            <a :href="extractLinkFromText(message.text)" target="_blank" class="link-preview">
                                                                <img v-if="linkPreviews[message.id].image" :src="linkPreviews[message.id].image" alt="Link preview image" />
                                                                <div>
                                                                    <h5>{{ linkPreviews[message.id].title }}</h5>
                                                                    <p>{{ linkPreviews[message.id].description }}</p>
                                                                </div>
                                                            </a>
                                                        </div>
                                                        <a :href="extractLinkFromText(message.text)" target="_blank" class="link-text">{{ message.text }}</a>
                                                    </div>
                                                    <div v-else>
                                                        <span class="message-text">{{ message.text }}</span>
                                                    </div>
                                                    <div class="message-reactions">
                                                        <span 
                                                            v-for="reaction in message.group_message_reaction" 
                                                            :key="reaction.id" 
                                                            class="reaction-emoji float-end"
                                                            @click="reaction.user_id === props.currentUser.id ? removeReaction(reaction) : null"
                                                        >
                                                            {{ reaction.emoji }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <button class="btn" @click="toggleEmojiPopover(message.id)"><i class="fa-regular fa-face-smile"></i></button>
                                                
                                                <small class="text-muted small">
                                                    <strong class="message-sender-name">{{ getMessageSenderName(message.sender_id) }}</strong>
                                                    <sup v-if="isOnline(message.sender_id)" class="online-dot"></sup>
                                                    <sup v-else class="offline-dot"></sup>
                                                    {{ formatTime(message.created_at) }}
                                                    <template v-if="message.sender_id === currentUser.id">
                                                        <box-icon v-if="message.status === 'not_delivered'" name='time' size="xs"></box-icon>
                                                        <box-icon v-if="message.status === 'sent'" name='check' size="xs"></box-icon>
                                                        <box-icon v-if="message.status === 'delivered'" name='check-double' size="xs"></box-icon>
                                                        <box-icon v-if="message.status === 'read'" name='check-double' color="blue" size="xs"></box-icon>
                                                    </template>
                                                </small>
                                                <!-- Show options only for messages sent by the current user -->
                                                <div v-if="message.sender_id === currentUser.id" class="dropdown position-absolute top-0 end-0 p-1">
                                                    <button class="btn btn-link p-0" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa fa-ellipsis-v text-primary"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                                        <li><a class="dropdown-item" href="#" @click.prevent="replyToMessage(message.id)">Replay</a></li>
                                                        <li><a v-if="message.text" class="dropdown-item" href="#" @click.prevent="copyToClipboard(message.text)">Copy</a></li>
                                                        <li><a class="dropdown-item" href="#" @click.prevent="hideMessage(message.id)">Delete For Me</a></li>
                                                        <li><a class="dropdown-item" href="#" @click.prevent="deleteMessage(message.id)">Delete For Everyone</a></li>
                                                        <li><a class="dropdown-item" href="#" @click.prevent="startEditMessage(message.id, message.text)">Edit</a></li>
                                                        <li><a class="dropdown-item" href="#" @click.prevent="openForwardModal(message.id)">Forward</a></li>
                                                        <li><a class="dropdown-item" href="#" @click.prevent="selectMessages()">Select Messages</a></li>
                                                    </ul>
                                                </div>
                                                <div v-else class="dropdown position-absolute top-0 end-0 p-1">
                                                    <button class="btn btn-link p-0" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa fa-ellipsis-v text-primary"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                                        <li><a class="dropdown-item" href="#" @click.prevent="replyToMessage(message.id)">Replay</a></li>
                                                        <li><a v-if="message.text" class="dropdown-item" href="#" @click.prevent="copyToClipboard(message.text)">Copy</a></li>
                                                        <li><a class="dropdown-item" href="#" @click.prevent="openForwardModal(message.id)">Forward</a></li>
                                                        <li><a class="dropdown-item" href="#" @click.prevent="hideMessage(message.id)">Delete</a></li>
                                                        <li><a class="dropdown-item" href="#" @click.prevent="selectMessages()">Select Messages</a></li>
                                                    </ul>
                                                </div>
                                                
                                                <div v-if="showPopover && currentMessageId === message.id" class="emoji-popover">
                                                    <span v-for="emoji in availableEmojis" :key="emoji" class="emojis" @click="addReaction(emoji, message.id)">
                                                        {{ emoji }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="showEmojiPicker" class="emoji-tooltip">
                            <emoji-picker @emoji-click="addEmoji"></emoji-picker>
                        </div>
                        <!-- File preview section -->
                        <div v-if="filePreview" class="file-preview">
                            <div class="file-preview-content">
                                <img v-if="file.type.startsWith('image/')" :src="filePreview" alt="File preview" class="img-thumbnail" />
                                <div v-else>
                                    <i class="fa-solid fa-file"></i>
                                    <p>{{ fileName }}</p>
                                </div>
                                <button class="btn btn-danger btn-sm file-remove-btn" @click="removeFile">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Audio preview after recording -->
                        <div v-if="audioBlobUrl" class="audio-preview mt-2">
                            <audio controls :src="audioBlobUrl" class="w-75"></audio>
                            <button @click="cancelRecording" class="btn text-danger float-end">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div v-if="repliedMessage"
                            class="replay-preview"
                            style="max-width: 100%; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                            <div class="d-flex align-items-start">
                                <div>
                                    <strong class="d-block text-primary">
                                        Replying to {{ getMessageSenderName(repliedMessage.sender_id) }}
                                    </strong>
                                    <div v-if="repliedMessage.text" class="text-secondary" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">
                                        "{{ repliedMessage.text }}"
                                    </div>
                                    <div v-else="repliedMessage.attachment" class="text-secondary" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">
                                        "File"
                                    </div>
                                </div>
                                <button
                                class="btn btn-sm btn-outline-danger ms-auto"
                                @click="cancelReply"
                                style="border: none; background: transparent;"
                                >
                                <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer" v-if="!selectedMessages.length > 0">
                        <div v-if="!isMember">
                            <div class="input-group">
                                <small><i>You can't send messages to this group.</i></small>
                                <small><i>You have been removed from the group by the admin.</i></small><br>
                            </div>
                        </div>
                        <div v-else>
                            <form @submit.prevent="sendMessage">
                                <div class="input-group">
                                    <div v-if="!isRecording">
                                        <button class="btn btn-light" type="button" @click="triggerFileInput">
                                            <i class="fa-solid fa-paperclip"></i>
                                        </button>
                                        <button class="btn btn-light" type="button" @click="toggleEmojiPicker">
                                            <i class="fa-solid fa-face-smile"></i>
                                        </button>
                                    </div>
                                    <button class="btn btn-light" type="button" @click="startRecording" v-if="!isRecording">
                                        <i class="fa fa-microphone"></i>
                                    </button>
                                    <input type="file" ref="fileInput" @change="handleFileUpload" style="display: none;" :disabled="isRecording"/>
                                    <input v-if="!isRecording" v-model="newMessage" type="text" class="form-control" placeholder="Type a message..." aria-label="Message">
                                    <button class="btn btn-primary" type="submit" v-if="!isRecording">Send</button>
                                </div>
                                <!-- Recording UI -->
                                <div v-if="isRecording" class="recording-ui">
                                    <div class="recording-indicator d-flex align-items-center">
                                        <div class="sound-analyzer">
                                            <div v-for="index in 100" :key="index" class="bar"></div>
                                        </div>
                                        <button @click="stopRecording" class="btn text-danger float-end">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sliding Footer Navigation -->
                    <div v-if="selectedMessages.length > 0" class="card-footer footer-nav">
                        <div class="input-group">
                            <button class="btn btn-light" type="button" @click="copySelectedMessages">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                            <button class="btn btn-light" type="button" @click="deleteSelectedMessages">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Group Edit Modal -->
        <div v-if="showEditGroupModal" class="modal fade show" tabindex="-1" aria-labelledby="editGroupModalLabel" aria-hidden="false" style="display: block;" ref="editModal">
            <div class="modal-dialog" role="dialog" aria-modal="true">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editGroupModalLabel">Edit Group</h5>
                        <button type="button" class="btn-close" @click="closeEditGroupModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="updateGroup">
                            <div class="mb-3">
                                <label for="groupName" class="form-label">Group Name</label>
                                <input type="text" class="form-control" id="groupName" v-model="groupData.name" required>
                            </div>
                            <div class="mb-3">
                                <label for="groupDescription" class="form-label">Group Description</label>
                                <textarea class="form-control" id="groupDescription" v-model="groupData.description" rows="3"></textarea>
                            </div>
                            <!-- Checkbox for Private Group -->
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="isPrivate" v-model="groupData.is_private" true-value="1" false-value="0">
                                <label class="form-check-label" for="isPrivate">
                                    Private Group
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </form>
                        <div class="m-3">
                            <label for="groupUsers" class="form-label">Members</label>
                            <ul class="list-group">
                                <li v-for="user in groupData.users.filter(user => parseInt(user.is_removed) === 0)" :key="user.id" class="list-group-item">
                                    {{ user.name }}
                                    <button class="btn btn-danger btn-sm file-remove-btn" @click="removeMember(user.id)">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- forward message modal -->
        <div v-if="showForwardModal" class="modal-overlay">
            <div class="modal-container">
                <div class="modal-header">
                    <h5 class="modal-title">Forward Message</h5>
                    <button type="button" class="close-button" @click="cancelForward">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="userSelect">Select User to Forward Message:</label>
                        <select v-model="selectedUser" class="form-control" id="userSelect" @change="deselectGroup">
                            <option disabled selected value="">Please select a user</option>
                            <option v-for="user in users" :key="user.id" :value="user">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group mt-3">
                        <label for="groupSelect">Select Group to Forward Message:</label>
                        <select v-model="selectedGroup" class="form-control" id="groupSelect" @change="deselectUser">
                            <option disabled selected value="">Please select a group</option>
                            <option v-for="group in groups" :key="group.id" :value="group">
                                {{ group.name }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer mt-3">
                    <button class="btn btn-secondary" @click="cancelForward">Cancel</button>
                    <button class="btn btn-primary" @click="forwardMessage">Forward</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { watch } from 'vue';
import axios from 'axios';
import 'emoji-picker-element';
import 'boxicons';

const props = defineProps({
    group: {
        type: Object,
        required: true
    },
    currentUser: {
        type: Object,
        required: true
    },
    users: {
        type: Array,
        required: true
    }
});

const messages = ref([]);
const newMessage = ref('');
const messageContainer = ref(null);
const editingMessageId = ref(null);
const editedMessageText = ref('');
const showEmojiPicker = ref(false);
const fileInput = ref(null);
const file = ref(null);
const fileName = ref('');
const filePreview = ref(null);
const repliedMessage = ref(null);
const showEditGroupModal = ref(false);
const groupData = ref({
    name: props.group.name,
    description: props.group.description,
    is_private: props.group.is_private,
    users: props.users,
});
const isRecording = ref(false);
const mediaRecorder = ref(null);
const audioChunks = ref([]);
const audioBlobUrl = ref(null);
const audioBlob = ref(null);
const speedOptions = ref([1, 1.5, 2, 0.5]);
const showForwardModal = ref(false);
const messageToForward = ref(null);
const users = ref([]);
const selectedUser = ref(null);
const selectedGroup = ref(null);
const groups = ref([]);
const availableEmojis = ['😀', '❤️', '😂', '😮', '😢', '👍'];
const showPopover = ref(false);
const currentMessageId = ref(null);
const linkPreviews = ref({});
const isLoading = ref(false);
const selectedMessages = ref([]);
const isSelectingMessages = ref(false);
const showFooter = ref(false);
const isMember = ref(true);
const onlineUsers = ref([]);

const speedIndexMap = ref(new Map());
const audioRefs = ref(new Map());

const isOnline = (userId) => {
    return onlineUsers.value.some(user => user.id === userId);
};

const checkMembershipStatus = () => {
    isMember.value = groupData.value.users.some(user => 
        user.id === props.currentUser.id && parseInt(user.is_removed) === 0
    );
};

const toggleFooter = () => {
    showFooter.value = selectedMessages.value.length > 0;
};

const selectMessages = () => {
  isSelectingMessages.value = !isSelectingMessages.value;
};

const closeSeletedMessages = () => {
    selectedMessages.value = [];
    isSelectingMessages.value = false;
};

const copySelectedMessages = () => {
    const formattedMessages = selectedMessages.value.map(msgId => {
        const message = messages.value.find(msg => msg.id === msgId);
        const senderName = message.sender_id === props.currentUser.id 
            ? props.currentUser.name 
            : props.users.find(user => user.id === message.sender_id)?.name || "Unknown Sender";

        const messageText = message.text;
        const messageTime = new Date(message.created_at).toLocaleString();

        return `${senderName}: ${messageTime}\n${messageText}`;
    }).join("\n\n");
    navigator.clipboard.writeText(formattedMessages).then(() => {
        console.log("Copied to clipboard:", formattedMessages);
        selectedMessages.value = [];
        isSelectingMessages.value = false;
    }).catch(err => {
        console.error("Failed to copy: ", err);
    });
};

const deleteSelectedMessages = async () => {
    try {
        console.log("Deleting messages:", selectedMessages.value);
        const response = await axios.post('/delete/multiple/group/messages', {
            messageIds: selectedMessages.value
        });
        if (response.status === 200) {
            console.log("Messages deleted successfully:", response.data);
            messages.value = messages.value.filter(msg => !selectedMessages.value.includes(msg.id));
            selectedMessages.value = [];
            isSelectingMessages.value = false;
        }
    } catch (error) {
        console.error("Error deleting messages:", error);
    }
};

const getCurrentSpeed = (messageId) => {
    return speedOptions.value[speedIndexMap.value.get(messageId) || 0];
};

const changeSpeed = (messageId) => {
    const audioPlayer = audioRefs.value.get(messageId);
    if (audioPlayer) {
        const currentSpeedIndex = speedIndexMap.value.get(messageId) || 0;
        const newSpeedIndex = (currentSpeedIndex + 1) % speedOptions.value.length;
        speedIndexMap.value.set(messageId, newSpeedIndex);
        audioPlayer.playbackRate = speedOptions.value[newSpeedIndex];
    } else {
        console.warn(`Audio element for message ${messageId} not found.`);
    }
};

const startRecording = async () => {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        console.error("getUserMedia not supported on your browser!");
        return;
    }

    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    mediaRecorder.value = new MediaRecorder(stream);
    audioChunks.value = [];

    mediaRecorder.value.ondataavailable = event => {
        audioChunks.value.push(event.data);
    };

    mediaRecorder.value.onstop = () => {
        const blob = new Blob(audioChunks.value, { type: 'audio/wav' });
        audioBlob.value = blob; // Store the blob
        audioBlobUrl.value = URL.createObjectURL(blob); // Create URL for audio preview
        audioChunks.value = []; // Clear chunks after recording
        stream.getTracks().forEach(track => track.stop()); // Stop all media tracks
    };

    mediaRecorder.value.start();
    isRecording.value = true;
};

const stopRecording = () => {
    if (mediaRecorder.value && isRecording.value) {
        mediaRecorder.value.stop();
        isRecording.value = false;
    } else {
        console.warn("Recording is not currently in progress.");
    }
};

const cancelRecording = () => {
    audioBlob.value = null;
    audioBlobUrl.value = null;
    isRecording.value = false; // Reset recording state if needed
};

const playNotificationSound = () => {
    const audio = new Audio('/sounds/notifications.mp3');
    audio.play();
};

const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const date = new Date(dateString);
    return isNaN(date.getTime()) ? 'Invalid Date' : date.toLocaleDateString(undefined, options);
};

const formatMessageByDate = (dateString) => {
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    const oneWeekAgo = new Date();
    oneWeekAgo.setDate(today.getDate() - 7);

    if (date.toDateString() === today.toDateString()) {
        return "Today";
    } else if (date.toDateString() === yesterday.toDateString()) {
        return "Yesterday";
    } else if (date > oneWeekAgo) {
        return date.toLocaleDateString(undefined, { weekday: 'long' }); // Day of the week for recent messages
    } else {
        return date.toLocaleDateString(); // Full date for older messages
    }
};

const groupedMessages = computed(() => {
    const groups = {};
    messages.value.forEach((message) => {
        const dateKey = formatMessageByDate(message.created_at);
        if (!groups[dateKey]) {
            groups[dateKey] = [];
        }
        groups[dateKey].push(message);
    });

    return groups;
});

const createdByUser = computed(() => {
    const user = props.users.find(user => user.id === props.group.created_by);
    if (user) {
        return user.id === props.currentUser.id ? 'You' : user.name;
    }
    return 'Unknown';
});

const triggerFileInput = () => {
    fileInput.value.click();
};

const openEditModal = () => {
    showEditGroupModal.value = true;
};

const closeEditGroupModal = () => {
    showEditGroupModal.value = false;
    groupName.value = '';
    groupDescription.value = '';
    isPrivate.value = false;
};

const removeMember = async (userId) => {
    try {
        await axios.delete(`/remove/group/${props.group.id}/member/${userId}`);
        groupData.value.users = groupData.value.users.filter(user => user.id !== userId);
    } catch (error) {
        console.error("Failed to remove member:", error);
    }
};

const updateGroup = async () => {
    try {
        const response = await axios.put(`/group/edit/${props.group.id}`, {
            name: groupData.value.name,
            description: groupData.value.description,
            is_private: groupData.value.is_private
        });

        const updatedGroup = response.data.group;
        closeEditGroupModal();
    } catch (error) {
        console.error("Failed to update group:", error);
    }
};

const toggleEmojiPopover = (messageId) => {
  if (currentMessageId.value === messageId && showPopover.value) {
    showPopover.value = false;
    currentMessageId.value = null;
  } else {
    currentMessageId.value = messageId;
    showPopover.value = true;
  }
}

const addReaction = async (emoji, messageId) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const formData = new FormData();
    formData.append('group_message_id', messageId);
    formData.append('emoji', emoji);
    formData.append('_token', csrfToken);
    try {
        const response = await axios.post(`http://127.0.0.1:8000/group/messages/reactions/${props.currentUser.id}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    } catch (error) {
        console.error('Error saving reaction:', error);
    }
    showPopover.value = false;
};

const removeReaction = async (reaction) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    try {
        const response = await axios.delete(`http://127.0.0.1:8000/group/messages/reactions/${reaction.id}`, {
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
        if (response.data.success) {
            messages.value = messages.value.map(message => {
                if (message.id === reaction.message_id) {
                    return {
                        ...message,
                        group_message_reaction: message.group_message_reaction.filter(r => r.id !== reaction.id)
                    };
                }
                return message;
            });
        }
    } catch (error) {
        console.error('Error removing reaction:', error);
    }
};

const isImage = (filename) => /\.(jpg|jpeg|png|gif)$/i.test(filename);
const isPDF = (filename) => /\.pdf$/i.test(filename);
const isVideo = (filename) => /\.(mp4|mkv|avi)$/i.test(filename);
const isAudio = (filename) => /\.(mp3|wav|ogg|flac|aac|m4a|wma|aiff|dsf|dff)$/i.test(filename);
const isLink = (text) => {
  const urlPattern = /(https?:\/\/[^\s]+)/g;
  return urlPattern.test(text);
};

const extractLinkFromText = (text) => {
  const urlPattern = /(https?:\/\/[^\s]+)/g;
  const match = text.match(urlPattern);
  return match ? match[0] : null;  // Return the first URL found, or null if none
};

const fetchLinkPreview = async (url, messageId) => {
    if (linkPreviews.value[messageId]) return;

    try {
        const response = await axios.get(`/link-preview?url=${encodeURIComponent(url)}`);
        linkPreviews.value[messageId] = response.data;
    } catch (error) {
        console.error('Error fetching link preview:', error);
    }
};

const openForwardModal = (messageId) => {
    messageToForward.value = messages.value.find(msg => msg.id === messageId);
    showForwardModal.value = true;
    fetchAllUsers();
    fetchUserGroups();
};

const fetchUserGroups = async () => {
    try {
        const response = await axios.get(`/get-groups/${props.currentUser.id}`); // Adjust the URL if necessary
        groups.value = response.data; // Store groups in a reactive variable
        console.log(groups.value)
    } catch (error) {
        console.error("Failed to fetch groups:", error);
    }
};

const fetchAllUsers = async () => {
    try {
        const response = await axios.get('http://127.0.0.1:8000/users'); // Adjust the URL if necessary
        users.value = response.data.filter(user => user.id !== props.currentUser.id);
    } catch (error) {
        console.error("Failed to fetch users:", error);
    }
};

const deselectUser = () => {
    if (selectedGroup.value) {
        selectedUser.value = null; // Deselect user if group is selected
    }
};

const deselectGroup = () => {
    if (selectedUser.value) {
        selectedGroup.value = null; // Deselect group if user is selected
    }
};

const forwardMessage = async () => {
    const targetId = selectedUser.value ? selectedUser.value.id : selectedGroup.value.id;
    const targetType = selectedUser.value ? 'user' : 'group';
    
    if (targetId && messageToForward.value) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const url = targetType === 'user'
                ? 'http://127.0.0.1:8000/group/messages/forward'
                : 'http://127.0.0.1:8000/group/messages/forward-group';

            const response = await axios.post(url, {
                message_id: messageToForward.value.id,
                recipient_id: targetId,
                _token: csrfToken
            });

            if (response.data.redirect) {
                window.location.href = response.data.redirect; // Redirect to the chat page
            }
            cancelForward(); // Reset modal
        } catch (error) {
            console.error("Failed to forward message:", error);
        }
    }
};

const cancelForward = () => {
    showForwardModal.value = false;
    messageToForward.value = null;
    selectedUser.value = null;
};

const handleFileUpload = () => {
    const selectedFile = fileInput.value.files[0];
    if (selectedFile) {
        file.value = selectedFile;
        fileName.value = selectedFile.name;

        if (selectedFile.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                filePreview.value = e.target.result;
            };
            reader.readAsDataURL(selectedFile);
        } else {
            filePreview.value = '/file.png'; // Set a default file icon or URL for non-image files
        }
    }
};

const formatFileSize = (bytes, decimals = 2) => {
    if (bytes === 0) return '0 Byte';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

const removeFile = () => {
    file.value = null;
    fileName.value = '';
    filePreview.value = null;
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messageContainer.value) {
            messageContainer.value.scrollTo({
                top: messageContainer.value.scrollHeight,
                behavior: "smooth",
            });
        }
    });
};

const fetchMessages = async () => {
    try {
        const response = await axios.get(`http://127.0.0.1:8000/group/${props.group.id}/messages`);
        if (isLoading.value) return;
        messages.value = response.data;

        messages.value.forEach(message => {
            if (isLink(message.text)) {
                const url = extractLinkFromText(message.text);
                if (url) {
                    fetchLinkPreview(url, message.id);
                }
            }
        });
    } catch (error) {
        console.error("Failed to fetch messages:", error);
    }
};

const sendMessage = async () => {
    if (newMessage.value.trim() !== '' || file.value || audioBlob.value) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const formData = new FormData();
            formData.append('message', newMessage.value);
            formData.append('_token', csrfToken);
            if (repliedMessage.value) {
                formData.append('replied_to', repliedMessage.value.id);
            }
            if (file.value) {
                formData.append('file', file.value);
            }
            if (audioBlob.value) {
                formData.append('audio', audioBlob.value, 'recording.wav');
            }

            const response = await axios.post(`http://127.0.0.1:8000/group/${props.group.id}/messages`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });

            const existingMessage = messages.value.find(msg => msg.id === response.data.id);
            if (!existingMessage) {
                messages.value.push({ ...response.data, status: 'sent' });
            }
            newMessage.value = '';
            file.value = null;
            fileName.value = '';
            filePreview.value = null;
            repliedMessage.value = null;
            audioBlob.value = null;
            audioBlobUrl.value = null;
            scrollToBottom();
        } catch (error) {
            console.error("Failed to send message:", error);
        }
    }
};

const replyToMessage = (messageId) => {
    repliedMessage.value = messages.value.find(msg => msg.id === messageId);
    newMessage.value = ''; // Activate the input field
};

const cancelReply = () => {
    repliedMessage.value = null;
};

const getMessageSenderName = (senderId) => {
    if (senderId === props.currentUser.id) {
        return 'You';
    } else {
        const user = props.users.find(user => user.id === senderId);
        return user ? user.name : 'Unknown'; // Fallback to 'Unknown' if user is not found
    }
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        console.log("Copied to clipboard");
        // Optionally, you can show a notification or message to the user here
    } catch (error) {
        console.error("Failed to copy:", error);
    }
};

const formatTime = (datetime) => {
    const now = new Date();
    const messageTime = new Date(datetime);
    const diffInHours = Math.abs(now - messageTime) / 36e5;
    if (diffInHours > 24) {
        const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        return messageTime.toLocaleDateString([], options);
    } else {
        const options = { hour: '2-digit', minute: '2-digit' };
        return messageTime.toLocaleTimeString([], options);
    }
};

const deleteMessage = async (messageId) => {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        await axios.put(`http://127.0.0.1:8000/delete/group/${messageId}/messages`, {
            data: { _token: csrfToken }
        });
        messages.value = messages.value.filter(message => message.id !== messageId);
    } catch (error) {
        console.error("Failed to delete message:", error);
    }
};

const hideMessage = async (messageId) => {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        await axios.post(`http://127.0.0.1:8000/hide/group/${messageId}/messages`, null, {
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
        // Remove the message from the local messages array
        messages.value = messages.value.filter(message => message.id !== messageId);
    } catch (error) {
        console.error("Failed to delete message:", error.response ? error.response.data : error);
    }
};

const startEditMessage = (messageId, messageText) => {
    editingMessageId.value = messageId;
    editedMessageText.value = messageText;
};

const updateMessage = async (messageId) => {
    if (editedMessageText.value.trim() !== '') {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await axios.put(`http://127.0.0.1:8000/edit/group/${messageId}/messages`, {
                message: editedMessageText.value,
                _token: csrfToken
            });
            messages.value = messages.value.map(message =>
                message.id === messageId ? { ...message, text: editedMessageText.value } : message
            );
            cancelEdit();
        } catch (error) {
            console.error("Failed to update message:", error);
        }
    }
};

const clearChat = async () => {
    const confirmation = confirm("Are you sure you want to clear the chat? This action cannot be undone.");
    
    if (!confirmation) {
        console.log("Chat clearing canceled.");
        return;
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        await axios.post(`http://127.0.0.1:8000/group/messages/clear/${props.group.id}`, {
            _token: csrfToken
        });
        messages.value = []; // Clear messages on the frontend
        console.log("Chat cleared successfully.");
    } catch (error) {
        console.error("Failed to clear chat:", error);
    }
};

const cancelEdit = () => {
    editingMessageId.value = null;
    editedMessageText.value = '';
};
const toggleEmojiPicker = () => {
    showEmojiPicker.value = !showEmojiPicker.value;
};

const addEmoji = (event) => {
    newMessage.value += event.detail.unicode;
    toggleEmojiPicker();
};
watch(messages, () => {
    scrollToBottom();
});
onMounted(() => {
    checkMembershipStatus();
    fetchMessages();

    Echo.join(`group.chat.${props.group.id}`)
        .listen("GroupMessageSent", (response) => {
            const existingMessage = messages.value.find(msg => msg.id === response.message.id);
            const isCurrentUserMember = groupData.value.users.some(user => 
                user.id === props.currentUser.id && parseInt(user.is_removed) === 0
            );
            if (!existingMessage && isCurrentUserMember) {
                const newMessage = { ...response.message, is_deleted: 0 };
                messages.value.push(newMessage);
                fetchMessages();
                playNotificationSound();
                scrollToBottom();
            }
        })
        .listen('GroupMessageReaction', (event) => {
            playNotificationSound();
            fetchMessages();
        })
        .listen('GroupUpdate', (response) => {
            console.log('GroupUpdate event:', response);
            groupData.value = {
                ...groupData.value,
                ...response.group
            };
        })
        .listen("GroupMessageDelete", (event) => {
            fetchMessages();
            messages.value = messages.value.filter(message => message.id !== event.message.id);
        })
        .listen('GroupMessageHide', (event) => {
            fetchMessages();
        })
        .listen("GroupMessageUpdate", (response) => {
            messages.value = messages.value.map(message =>
                message.id === response.message.id ? response.message : message
            );
        })
        .listen("RemoveGroupMember", (event) => {
            console.log('Member removed:', event);
            if (typeof event.user === 'string' || typeof event.user === 'number') {
                groupData.value.users = groupData.value.users.filter(user => user.id !== parseInt(event.user, 10));
                
                if (event.user === props.currentUser.id.toString()) {
                    isMember.value = false;
                }
            } else {
                console.error('User is not a valid ID:', event.user);
            }
        })
        .listen("AddGroupMember", (event) => {
            console.log('Member added:', event);
            if (typeof event.user === 'string' || typeof event.user === 'number') {
                const userId = parseInt(event.user, 10);
                const existingUser = groupData.value.users.find(user => user.id === userId);
                if (!existingUser) {
                    groupData.value.users.push({
                        id: userId,
                        name: event.name,
                        is_removed: '0' 
                    });
                    if (userId === props.currentUser.id) {
                        checkMembershipStatus();
                    }
                } else {
                    console.log('User is already a member:', existingUser);
                }
            } else {
                console.error('User is not a valid ID:', event.user);
            }
        });
    Echo.join('user-status')
    .here((users) => {
        onlineUsers.value = users.filter(user => props.users.some(u => u.id === user.id));
    })
    .joining((user) => {
        if (props.users.some(u => u.id === user.id)) {
            onlineUsers.value.push(user);
        }
    })
    .leaving((user) => {
        onlineUsers.value = onlineUsers.value.filter(u => u.id !== user.id);
    })
    .listen('UserOnline', (event) => {
    });
});
</script>

<style scoped>
.chat-body {
    height: 600px;
    overflow-y: auto;
    background-color: #f5f5f5;
}

.chat-body::-webkit-scrollbar {
    display: none;
}

.date-separator hr {
    border: 0;
    height: 1px;
    background-color: #ccc;
}

.date-header {
    font-size: 0.875rem; /* Adjust the size as needed */
    color: #6c757d; /* Muted text color */
}

.message-bubble-in {
    background-color: #e5e5ea;
    color: #000;
    position: relative;
}

.message-bubble-out {
    background-color: #25D366;
    color: #fff;
    position: relative;
}
.file-card{
    background-color: #25D366;
    color: #000;
}

.file-card-receiver{
    color: #000;
}

.file-card-icons{
    color: #fff;
}
.file-card-icons-receiver{
    color: #000;
}

.input-group {
    display: flex;
    align-items: center;
}

.btn-primary {
    border-radius: 20px;
}
.btn-success {
    border-radius: 20px;
}

.text-muted {
    font-size: 0.8rem;
}
.emoji-tooltip {
    position: absolute;
    bottom: 50px; /* Adjust as needed */
    left: 0;
    z-index: 1000;
    background-color: #fff;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 5px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.file-preview {
    position: absolute;
    bottom: 50px;
    left: 20px;
    z-index: 1000;
    background-color: #fff;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 5px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.file-preview-content {
    display: flex;
    align-items: center;
}

.img-thumbnail {
    max-width: 200px;
    max-height: 200px;
    margin-right: 10px;
}
.video-thumbnail {
    max-width: 200px;
    max-height: 200px;
    margin-right: 10px;
}

.file-remove-btn {
    position: absolute;
    top: 5px;
    right: 5px;
}

.file-download {
    margin-top: 10px;
}
.file-icon {
    font-size: 34px;
}
.replied-message-container {
  display: flex;
  align-items: flex-start;
  padding-left: 12px;
  border-left: 3px solid #007bff; /* Accent color for reply */
  background-color: #f8f9fa; /* Light background color */
  border-radius: 8px;
  margin-bottom: 10px;
}

.replied-message-content {
  max-width: 100%; /* Adjust to keep content compact */
  background-color: #ffffff; /* Contrast background */
  border: 1px solid #e3e3e3; /* Light border */
  border-radius: 8px;
  padding: 8px 12px; /* Add padding */
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); /* Subtle shadow */
}

.replied-message-sender {
  color: #007bff; /* Highlighted sender name */
  font-weight: 600;
  margin-bottom: 3px;
  display: block;
}

.replied-message-text {
  color: #495057; /* Text color for better readability */
  font-size: 14px;
  word-wrap: break-word; /* Handle long text */
  margin-top: 3px;
}
.replay-preview{
    position: absolute;
    width: 60%;
    bottom: 50px;
    left: 20px;
    z-index: 1000;
    background-color: #fff;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 5px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.message-bubble-in, .message-bubble-out {
  margin-top: 2px;
}

@media (max-width: 768px) {
  .replied-message-content {
    max-width: 100%; /* Full width for smaller screens */
  }
}

.audio-preview {
    position: absolute;
    width: 60%;
    bottom: 50px;
    left: 20px;
    z-index: 1000;
    background-color: #fff;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 5px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.recording-ui {
    display: flex;
    height: 30px;
    align-items: center;
}

.sound-analyzer {
    display: flex;
    align-items: flex-end;
    margin-right: 10px;
}

.bar {
    width: 2px; /* Adjust width as needed */
    height: 10px; /* Initial height */
    background-color: #007bff; /* Change color as needed */
    margin: 0 1px; /* Adjust spacing */
    animation: pulse 0.5s infinite;
}

@keyframes pulse {
    0%, 100% {
        height: 10px; /* Initial height */
    }
    50% {
        height: 20px; /* Height at peak */
    }
}

.playSpeed{
    border-radius: 50%; 
    border:none; background-color: #25D366; 
    color: #000; font-weight: 700; 
    position: absolute; 
    bottom: 16px; 
    right: 15px;
}

.playSpeed-receiver{
    border-radius: 50%; 
    border:none; background-color: #f1f3f4; 
    color: #000; font-weight: 700; 
    position: absolute; 
    bottom: 16px; 
    right: 15px;
}

audio::-webkit-media-controls-enclosure {
    overflow: hidden; 
}
audio::-webkit-media-controls-playback-rate-button,
audio::-webkit-media-controls-download-button {
    display: none !important;
}
audio::-webkit-media-controls-mute-button {
    display: none !important;
}
.audio-thumbnail::-webkit-media-controls-play-button,
.audio-thumbnail::-webkit-media-controls-panel {
    background-color: #25D366;
    color: #25D366;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-container {
    background: white;
    padding: 20px;
    border-radius: 5px;
    width: 400px;
}

.modal-header, .modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.close-button {
    background: none;
    border: none;
    font-size: 1.5rem;
}

.emoji-popover {
  position: absolute;
  background: white;
  border: 1px solid #ccc;
  padding: 5px;
  border-radius: 8px;
  display: flex;
  gap: 10px;
  box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
  z-index: 10;
}
.emojis:hover{
    cursor: pointer;
    font-size: 18px;
    transition-duration: .3s;
}

.reactions {
  margin-top: 5px;
}

button {
  margin-right: 10px;
  cursor: pointer;
}
.reaction-emoji {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #f1f1f1;  /* Light background for contrast */
    border: 1px solid #ccc;
    font-size: 1.2em;
    margin-left: 5px;
    transition: transform 0.2s ease, background-color 0.2s ease; /* Smooth transition for hover effect */
    cursor: pointer; /* Change cursor to pointer for interactivity */
}

.reaction-emoji:hover {
    transform: scale(1.3); /* Zoom effect */
    background-color: #e0e0e0; /* Slightly darker background on hover */
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); /* Adding a subtle shadow */
}

/* Extra styling for better UI */
.message-reactions {
    margin-top: 8px; /* Add some spacing above reactions */
}

.text-white.message-bubble-out, .bg-light.message-bubble-in {
    padding: 10px 15px;
    border-radius: 20px;
    position: relative;
    margin-bottom: 10px;
}

.message-bubble-in {
    background-color: #f1f1f1;
    color: black;
}

.float-end {
    float: right;
    margin-left: 10px; /* Spacing between reactions */
}

.link-preview {
    display: flex;
    align-items: flex-start;
    border-radius: 8px;
    padding: 10px;
    margin-top: 10px;
    max-width: 400px;
    transition: box-shadow 0.3s ease;
    text-decoration: none;
}

.link-preview img {
    max-width: 100px;
    max-height: 100px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 10px;
}

.link-preview div {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.link-preview h5 {
    font-size: 16px;
    margin: 0;
    font-weight: bold;
}

.link-preview p {
    font-size: 14px;
    margin: 5px 0 0;
    color: #555;
}

.link-text {
    color: #01060c !important;
    text-decoration: none;
    font-size: 14px;
}

.link-text:hover {
    text-decoration: underline;
}

/* Custom checkbox styling */
.form-check-input {
    width: 24px; /* Adjust size */
    height: 24px; /* Adjust size */
    border: 2px solid #007bff; /* Border color */
    border-radius: 50%; /* Make it circular */
    appearance: none; /* Remove default checkbox style */
    outline: none; /* Remove outline */
    transition: background-color 0.3s ease, border-color 0.3s ease; /* Smooth transition */
}

.form-check-input:checked {
    background-color: #007bff; /* Background color when checked */
    border-color: #007bff; /* Border color when checked */
}

.form-check-input:checked::after {
    content: '';
    position: absolute;
    top: 4px;
    left: 4px;
    width: 12px; /* Size of the inner circle */
    height: 12px; /* Size of the inner circle */
    border-radius: 50%; /* Make it circular */
    background-color: white; /* Inner circle color */
}

.form-check-label {
    margin-left: 10px; /* Space between checkbox and text */
    cursor: pointer; /* Pointer cursor for better UX */
}

/* Hover effect */
.form-check-input:hover {
    border-color: #0056b3; /* Darker border on hover */
}

/* Optional: Add some spacing around the checkbox */
.file-download {
    margin-bottom: 10px; /* Adjust as needed */
}

.footer-nav {
    transition: bottom 0.3s ease;
}

.footer-nav-enter-active, .footer-nav-leave-active {
    transition: bottom 0.3s ease;
}

.footer-nav-enter, .footer-nav-leave-to {
    bottom: 0; /* Slide in from bottom */
}

.online-dot, .offline-dot {
  height: 10px;
  width: 10px;
  border-radius: 50%;
  display: inline-block;
}

.online-dot {
  background-color: #28a745;
}

.offline-dot {
  background-color: #6c757d;
}

.system-message {
    border-radius: 8px;
    padding: 10px;
    font-style: italic;
}
</style>
