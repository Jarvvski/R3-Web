<template>
    <div>
        <table class="table-list">
            <thead>
                <tr>
                    <th v-for="key in columns" @click="sortBy(key)" :class="{ 'table-list__header-item__sort--asc': sortOrders[key] > 0, 'table-list__header-item__sort--desc': sortOrders[key] < 1  }" class="table-list__header-item">
                        {{ $t(key) | capitalize }}
                    </th>
                </tr>
            </thead>

            <tbody class="table-list__data">

                <tr
                    v-if="filteredData.length > 0"
                    v-for="entry in filteredData"
                    class="table-list__row">

                    <td
                        v-for="key in columns"
                        :class="{ 'table-list__item--bold': key == 'user', 'table-list__item--responsive-header': key == 'user' }"
                        class="table-list__item"
                        :data-title="ucfirst(key)">

                        <span class="table-list__item__text">
                            {{ entry[key] }}
                        </span>

                    </td>
                    <td>
                        <div
                                @click="deleteUser"
                                :data-url="entry.id"
                        >DELETE</div>
                    </td>
                    <td>
                        <div
                                @click="toggleAdmin"
                                :data-url="entry.id"
                        >TOGGLE ADMIN</div>
                    </td>

                </tr>

                <tr v-if="!waitingForData && emptyData" v-for="n in 1" class="table-list__row">
                    <td :colspan="columns.length" class="table-list__item">There's no Users here, how did you even get here?</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>

    import TableList from 'components/TableList.vue'

    import router from 'routes'
    import { ucfirst } from 'filters'

    import _each from 'lodash.foreach'

    export default {

        components: {
            TableList,
        },

        props: {
            data: Array,
            columns: Array,
            filterKey: String,
            noData: Boolean,
            onDelete:Function,
            onToggleAdmin:Function
        },

        data () {

            var sortOrders = {}
                this.columns.forEach(function (key) {
                sortOrders[key] = 1
            })

            return {
                sortKey: '',
                sortOrders: sortOrders
            }
        },

        methods: {

            sortBy: function(key) {
                this.sortKey = key
                this.sortOrders[key] = this.sortOrders[key] * -1
            },

            deleteUser (event) {

                event.stopPropagation();
                console.log('deleting User', event.currentTarget.getAttribute('data-url'))
                this.onDelete(event.currentTarget.getAttribute('data-url'));
            },

            toggleAdmin (event) {

                event.stopPropagation();
                console.log('toggling User', event.currentTarget.getAttribute('data-url'))
                this.onToggleAdmin(event.currentTarget.getAttribute('data-url'));
            },
            ucfirst,

        },

        computed: {


            waitingForData () {

                return this.noData
            },

            emptyData () {

                return !this.noData && this.data.length === 0
            },

            filteredData () {

                var sortKey = this.sortKey
                var filterKey = this.filterKey && this.filterKey.toLowerCase()

                var order = this.sortOrders[sortKey] || 1
                var data = this.data

                // Searching?
                if (filterKey) {
                    data = data.filter(function(row) {
                        return Object.keys(row).some(function(key) {
                            return String(row[key]).toLowerCase().indexOf(filterKey) > -1
                        })
                    })
                }

                // Ordering columns
                if (sortKey) {
                    data = data.slice().sort(function(a, b) {
                        a = a[sortKey]
                        b = b[sortKey]
                        return (a === b ? 0 : a > b ? 1 : -1) * order
                    })
                }

                return data
            },
        },

        filters: {

            capitalize (str) {
                return str.charAt(0).toUpperCase() + str.slice(1)
            }
        },
    }
</script>

<style lang="stylus">

    .table-list__item__progress__icon
        margin-right 10px
        display inline-block
        animation tableListSpin 3s infinite linear
        transform-origin 50% 70%

    .table-list__row:hover
        cursor pointer

    .table-list__row--empty:hover
        cursor default

    .table-list__row--in-progress:hover
        cursor not-allowed
</style>
