<template>
    <div>
        <container>

            <feedback
                    v-if="error"
                    type="error"
                    class="margin__top--medium">
                <span slot="message" v-html="error"></span>
            </feedback>

        </container>

        <container>

            <list-search
                    :title="ucfirst($t('users'))"
                    :listTotal="listLength"
                    @searched="updateSearchQuery($event)"
                    :placeholder="$t('search-users')">
            </list-search>
            <TableListUsers
                    :data="listData"
                    :columns="listColumns"
                    :filter-key="searchQuery"
                    :onDelete="deleteUser"
                    :onToggleAdmin="toggleAdmin"
                    :noData="noData"
            />
        </container>


    </div>
</template>

<script>
    import axios from 'http'
    import ListSearch from 'components/ListSearch.vue'
    import Container from 'components/Container.vue'
    import TableListUsers from 'components/TableListUsers.vue'
    import Feedback from 'components/Feedback.vue'

    import {ucfirst} from 'filters'

    export default {

        props: {},
        components: {
            Container,
            ListSearch,
            TableListUsers,
            Feedback,
        },

        data() {
            return {

                users: [],
                error: '',
                searchQuery: '',
                listColumns: ['name', 'email', 'admin'],
            }
        },


        mounted() {

            console.log('Users mounted', this.error)
            this.fetchUsers()

        },

        methods: {

            updateSearchQuery(val) {

                this.searchQuery = val
            },
            ucfirst,
            fetchUsers() {
                axios.get(`/users?api_token=${this.$store.state.settings.adminToken}`)
                    .then(response => {
                        console.log('Got users', response.data);
                        this.users = response.data;
                    })
                    .catch(error => {

                        this.error = `Failed to fetch users!`
                        console.log(error);
                    })
            },

            deleteUser(id) {
                axios.post(`/users/delete/${id}?api_token=${this.$store.state.settings.adminToken}`)
                    .then(response => {
                        console.log('deleted user', response.data);
                        this.fetchUsers();
                    })
                    .catch(error => {
                        this.error = `Failed to delete user!`
                        console.error(error);
                    })
            },

            toggleAdmin(id) {
                axios.post(`/users/toggleAdmin/${id}?api_token=${this.$store.state.settings.adminToken}`)
                    .then(response => {
                        console.log('toggled user', response.data);
                        this.fetchUsers();
                    })
                    .catch(error => {
                        this.error = `Failed to toggle user!`
                        console.error(error);
                    })
            },
        },

        computed: {

            noData() {
                return this.users == null;
            },

            listData() {
                return this.users
            },

            listLength() {
                return this.users == null ? 0 : this.users.length
            },
        },
    }
</script>
