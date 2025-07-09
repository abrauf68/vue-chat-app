import {createRouter, createWebHistory} from 'vue-router';
import GroupsComponent from './components/GroupsComponent.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path:'/groups',
            component:GroupsComponent
        },
    ]
})

export default router;