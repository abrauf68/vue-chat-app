<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        Groups
                        <!-- Trigger the modal with this button -->
                        <button class="btn btn-primary btn-sm float-end" @click="openCreateGroupModal">Create</button>
                    </div>
                    <div class="card-body">
                        <p v-if="groups.length === 0">No groups available.</p>
                        <div v-else class="row">
                            <div v-for="group in groups" :key="group.id" class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="card-header">
                                            <span>{{ group.name }}</span>
                                            <span class="badge float-end" :class="{'bg-success': group.is_private === '1', 'bg-secondary': group.is_private === '0'}">
                                                {{ group.is_private === '1' ? 'Private' : 'Public' }}
                                            </span>
                                        </div>
                                        <p class="card-text">{{ group.description }}</p>
                                        <button v-if="group.is_private === '0' || currentUser.id == group.created_by" class="btn btn-secondary mt-2 btn-sm mx-2" @click="openAddMembersModal(group.id)">Add Members</button>
                                        <a :href="`/group/chat/${group.id}`" class="btn btn-primary mt-2 btn-sm mx-2">Visit Group</a>
                                        <a v-if="currentUser.id == group.created_by" :href="`/group/delete/${group.id}`" class="btn btn-danger mt-2 btn-sm mx-2">Delete</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Group Modal -->
        <div v-if="showCreateGroupModal" class="modal fade show" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true" style="display: block;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createGroupModalLabel">Create New Group</h5>
                        <button type="button" class="btn-close" @click="closeCreateGroupModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="createGroup">
                            <div class="mb-3">
                                <label for="groupName" class="form-label">Group Name</label>
                                <input type="text" class="form-control" id="groupName" v-model="groupName" required>
                            </div>
                            <div class="mb-3">
                                <label for="groupDescription" class="form-label">Group Description</label>
                                <textarea class="form-control" id="groupDescription" v-model="groupDescription" rows="3"></textarea>
                            </div>
                            <!-- Checkbox for Private Group -->
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="isPrivate" v-model="isPrivate">
                                <label class="form-check-label" for="isPrivate">
                                    Private Group
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">Create</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Members Modal -->
        <div v-if="showAddMembersModal" class="modal fade show" tabindex="-1" aria-labelledby="addMembersModalLabel" aria-hidden="true" style="display: block;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addMembersModalLabel">Add Members</h5>
                        <button type="button" class="btn-close" @click="closeAddMembersModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="addMembers">
                            <div class="mb-3">
                                <label for="users" class="form-label">Select Users</label>
                                <select id="users" v-model="selectedUsers" multiple class="form-select">
                                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Members</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    currentUser: {
        type: Object,
        required: true
    }
});

const groupName = ref('');
const groupDescription = ref('');
const isPrivate = ref(false);
const showCreateGroupModal = ref(false);
const showAddMembersModal = ref(false);
const selectedUsers = ref([]);
const users = ref([]);
const groups = ref([]);
let currentGroupId = ref(null);

const fetchGroups = async () => {
    try {
        const response = await axios.get(`/get-groups/${props.currentUser.id}`);
        groups.value = response.data;
    } catch (error) {
        console.log('Failed to fetch groups:', error);
    }
};

const fetchUsers = async (groupId) => {
    try {
        fetchGroups();
        const response = await axios.get(`/get-users/${groupId}`);
        users.value = response.data;
    } catch (error) {
        console.log('Failed to fetch users:', error);
    }
};

const createGroup = async () => {
    if (groupName.value.trim() === '') {
        alert('Group name is required.');
        return;
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        await axios.post('/groups', {
            name: groupName.value,
            description: groupDescription.value,
            is_private: isPrivate.value ? '1' : '0',
            _token: csrfToken
        });

        console.log('Group Created');
        closeCreateGroupModal();
        fetchGroups(); // Refresh groups list
    } catch (error) {
        console.log('Failed to create group:', error);
    }
};

const openCreateGroupModal = () => {
    showCreateGroupModal.value = true;
};

const closeCreateGroupModal = () => {
    showCreateGroupModal.value = false;
    groupName.value = '';
    groupDescription.value = '';
    isPrivate.value = false;
};

const openAddMembersModal = (groupId) => {
    currentGroupId.value = groupId;
    fetchUsers(groupId); // Fetch users when opening the modal
    showAddMembersModal.value = true;
};

const closeAddMembersModal = () => {
    showAddMembersModal.value = false;
    selectedUsers.value = [];
};

const addMembers = async () => {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const response = await axios.post(`/groups/${currentGroupId.value}/add-members`, {
            users: selectedUsers.value,
            _token: csrfToken
        });
        console.log('Members Added', response.data);
        closeAddMembersModal();
    } catch (error) {
        console.error('Failed to add members:', error.response ? error.response.data : error);
    }
};

onMounted(() => {
    fetchGroups();
});
</script>

<style scoped>
/* Add any additional styling here */
</style>
