<template>
    <div>
        <main-header :title="title"></main-header>

        <container>

            <feedback
                    v-if="errorFeedback"
                    type="error"
                    class="margin__top--medium">
                <span slot="message" v-html="errorFeedback"></span>
            </feedback>

        </container>

        <MissionList
                :showDelete=true
                :onDelete="deleteMission"
        />

    </div>
</template>

<script>
    import MainHeader from 'components/MainHeader.vue'
    import MissionList from "views/missions/MissionList.vue";
    import Feedback from 'components/Feedback.vue'
    import Container from 'components/Container.vue'

    export default {
        components: {
            MissionList,
            MainHeader,
            Feedback,
            Container,
        },

        mounted() {

            console.log('Missions mounted', this.error)
            this.fetchMissions()

        },

        data () {
            return {
                errorFeedback: this.error,
            }
        },

        computed: {

            unitName() {
                return this.$store.state.settings.unitName
            },

            title() {
                return this.unitName ? `${this.unitName} - Page Not Found` : 'Page Not Found'
            },
        },

        watch: {
            unitName: function (name) {
                document.title = this.title
            }
        },

        methods: {

            fetchMissions() {

                let store = this.$store;
                store.commit("setMissionList", null);
                fetch('/api/missions/all')
                    .then(response => {

                        return response.json();
                    }).then(json => {
                    console.log('Got missions', json);
                    store.commit('setMissionList', json)
                }).catch(error => {
                    this.errorFeedback = `Failed to fetch missions!`
                    console.log(error);
                })
            },

            deleteMission(id) {
                fetch("/api/missions/delete/" + id + `?api_token=${this.$store.state.settings.adminToken}`, {
                    method: "POST"
                }).then(response => {
                    console.log('deleted mission', response.json());
                    this.fetchMissions();
                })
                    .catch(error => {
                        console.error(error);
                    })
            }
        }
    }
</script>
