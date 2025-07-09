import './bootstrap';
import { createApp } from 'vue';
import router from './routes';


const app = createApp({});

import ChatComponent from './components/ChatComponent.vue';
import GroupsComponent from './components/GroupsComponent.vue';
import GroupChatComponent from './components/GroupChatComponent.vue';
app.component('chat-component', ChatComponent);
app.component('group-component', GroupsComponent);
app.component('group-chat-component', GroupChatComponent);

app.mount('#app');
