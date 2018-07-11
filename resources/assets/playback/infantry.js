import _keyBy from 'lodash.keyby'
import _defaults from 'lodash.defaults'
import _each from 'lodash.foreach'
import axios from 'http'
import L from 'leaflet'
import 'leaflet-rotatedmarker'

import Playback from './index'
import Map from './map'
import Time from './time'
import { gameToMapPosX, gameToMapPosY } from './helpers/gameToMapPos'
import getFactionData from './helpers/getFactionData'
import shortestRotation from './helpers/shortestRotation'

class Infantry {

    constructor () {
        this.entities = {}
        this.positions = {}
        this.layer
        this.timeLastSeenKeyFrame = 10
    }

    loadEntities (missionId) {

        return new Promise((resolve, reject) => {
            axios.get(`/infantry/${missionId}`)
                .then(response => {

                    console.log('Infantry: Got infantry', response.data.length);

                    let data = response.data

                    this.entities = _keyBy(data, 'entity_id')

                    _each(this.entities, entity => {
                        entity.isPlayer = this.isPlayer(entity)
                    })

                    resolve()
                })
                .catch(error => {

                    console.error('Infantry: Error fetching mission infantry', error)

                    reject()
                })
        })
    }

    loadPositions (missionId) {

        return new Promise((resolve, reject) => {

            axios.get(`/positions/infantry/${missionId}`)
                .then(response => {

                    console.log('Infantry: Got infantry positions', response.data.length);

                    this.positions = response.data

                    // Pre-map all game points to map points to save processing time later
                    _each(this.positions, timeGroup => {

                        _each(timeGroup, pos => {

                            pos.x = gameToMapPosX(pos.x)
                            pos.y = gameToMapPosY(pos.y)

                        })

                    })

                    resolve()
                })
                .catch(error => {

                    console.error('Infantry: Error fetching infantry positions', error)

                    reject()
                })
        })
    }

    removeEntity (entity) {

        console.warn('Vehicles: removing old layer', entity.icon)

        this.layer.removeLayer(entity.layer)
    }

    processTime (missionTime) {

        if (this.positions.hasOwnProperty(missionTime)) {

            _each(this.positions[missionTime], posData => {

                // If this is the first key frame we've seen since the last batch
                // lets clear all current markers and prepare for the up to date ones
                // that are about to be added
                if (
                    posData.key_frame == '1' &&
                    (Time.currentMissionTime - this.timeLastSeenKeyFrame > 9)
                ) {
                    this.timeLastSeenKeyFrame = Time.currentMissionTime
                    //this.clearMarkers()
                }

                this.updateEntityPosition(posData)
            })
        }
    }

    // To avoid having to wait for up to 20 seconds for all static units to re-appear
    // we must look back in time to find our last key frame and populate the map with
    // that positional data first, then quickly skip ahead to the time we want
    processLastKeyFrame (missionTime) {

        if (missionTime < 1)
            return

        if (this.positions.hasOwnProperty(missionTime) && this.positions[missionTime][0].key_frame == '1') {
            this.processTime(missionTime)
        } else {
            missionTime--
            this.processLastKeyFrame(missionTime)
        }
    }

    updateEntityPosition (posData) {

        // Do we know of this entity? If not ignore
        if (!this.entities.hasOwnProperty(posData.entity_id)) {
            console.warn('Infantry: unknown entity', posData.entity_id)
            return
        }

        let entity = this.entities[posData.entity_id]

        // Has this entity ever been on the map?
        if (!entity.hasOwnProperty('layer'))
            this.addEntityToMap(entity)

        // Has this entity been on the map, but isn't right now?
        if (!this.layer.hasLayer(entity.layer)) {
            this.layer.addLayer(entity.layer)

            let tooltip = entity.layer.getTooltip();
            if(tooltip != null) {
                let toolTipElement = tooltip.getElement()
                if (posData.is_dead == '1')
                    toolTipElement.style.opacity = 0.4
                else
                    toolTipElement.style.opacity = 1
            }
        }

        // Store when we last moved this unit so we can decide to clean up later
        entity.missionTimeLastUpdated = posData.mission_time

        let mapPosition = Map.rc.unproject([posData.x, posData.y])

        // Update entity position
        entity.layer.setLatLng(mapPosition)

        // Update rotation
        this.setEntityRotation(entity, posData.direction)

        // Is this unit dead?
        // Is this unit dead? This helps when skipping back and forth through time
        if (entity.dead == null && posData.is_dead == '1') {
            entity.dead = true
            entity.layer.setOpacity(0.4)
        } else if (entity.dead != null && posData.is_dead == '0') {
            entity.dead = null
            entity.layer.setOpacity(1)
        }

        // Highlight unit?
        if (Playback.highlightUnit && Playback.highlightUnit == entity.entity_id) {

            Playback.highlightEntity(entity.entity_id)

            // Lets not continue panning to the unit if the user wants to look around the map
            if (Playback.trackingHighlightedUnit) {

                // Has the map view moved away from the tracked player? Lets bring it back into view
                let point = Map.handler.latLngToLayerPoint(entity.layer.getLatLng())
                let distance = point.distanceTo(Map.handler.latLngToLayerPoint(Map.handler.getCenter()))

                if (distance > 200)
                    Map.handler.panTo(Map.rc.unproject([posData.x, posData.y]))
            }
        }

        // Let's move the view to the starting area
        if (!Playback.centeredOnFirstPlayer && this.isPlayer(entity)) {

            Map.setView(mapPosition, 4)
            Playback.centeredOnFirstPlayer = true
        }
    }

    setEntityRotation (entity, newAngle) {

        // No point calculating for a rotation change if they are
        // facing the same direction
        if(newAngle == entity.currentAngle)
            return

        //let smoothAngle = shortestRotation(entity.currentAngle, newAngle);

        //console.log(`${entity.currentAngle} - ${newAngle} - ${smoothAngle}`)

        entity.currentAngle = newAngle

        entity.layer.setRotationAngle(newAngle);
    }

    addEntityToMap (entity) {

        let entityIcon = Map.getUnitIcon(entity.icon, entity.icon)
        let factionData = getFactionData(entity.faction)

        // Our unit marker image
        let icon = Map.prepareIcon(entityIcon, factionData)

        let marker = L.marker([0,0], { icon })

        let label = (this.isPlayer(entity)) ? entity.name : ''

        if (label)
            marker.bindTooltip(`<span class="map__label__text">${label}</span>`, {
                className: `map__label map__label__infantry`
            })

        // Create the marker, we aren't going to add it to the map
        // just yet so the position isn't important
        entity.layer = marker
    }

    getEntityById (entityId) {

        if (this.entities.hasOwnProperty(entityId))
            return this.entities[entityId]
        else
            return false
    }

    isPlayer (entity) {

        return (entity.player_id != "" && entity.player_id != "_SP_AI_") ? true : false
    }

    isPlayerByEntityId (entityId) {

        let entity = this.getEntityById(entityId)

        if (!entity)
            return false
        else
            return this.isPlayer(entity)
    }

    // If the unit is on the map lets remove it as
    // it is now in a vehicle
    getIn (entityId) {

        if (
            this.entities.hasOwnProperty(entityId) &&
            this.entities[entityId].hasOwnProperty('layer') &&
            this.layer.hasLayer(this.entities[entityId].layer)
        )
            this.layer.removeLayer(this.entities[entityId].layer)
    }

    // Unit has woken up, let's remove their opacity
    // TODO: What if this event is skipped? We need to look at if a unit has moved
    // since being marked as dead and remove opacity too
    awake (entityId) {

        if (
            this.entities.hasOwnProperty(entityId) &&
            this.entities[entityId].hasOwnProperty('layer') &&
            this.layer.hasLayer(this.entities[entityId].layer)
        )
            this.entities[entityId].layer.setOpacity(1)
    }

    getPlayers () {

        return new Promise((resolve, reject) => {

            let players = []

            _each(this.entities, entity => {

                // Is this unit a player?
                if (this.isPlayer(entity))
                    players.push(entity)
            })

            resolve(players)
        })
    }

    initMapLayer () {

        this.layer = new L.LayerGroup()
        this.layer.addTo(Map.handler)
    }

    clearMarkers () {
        console.log('Clearing infantry markers')
        this.layer.clearLayers()
    }
}

export default new Infantry
