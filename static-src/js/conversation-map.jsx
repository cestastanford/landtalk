/*
 *   Imports.
 */

import React from "react";
import { bool, number, string } from "prop-types";
import { downloadConversations } from "./rest";
import { GOOGLE_MAPS_JS_API_KEY } from "../.env";
import markerIcon from "../img/marker.png";
import selectedMarkerIcon from "../img/selected-marker.png";
import markerGroupIcon from "../img/marker-group.png";

/*
 *   Returns a promise for loading the Google Maps JS API.
 */

export const loadGoogleMapsAPI = () =>
  new Promise((resolve) => {
    if (window.google) resolve();
    else {
      window.googleMapsAPILoaded = resolve;
      const element = document.createElement("script");
      element.type = "text/javascript";
      element.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_JS_API_KEY}&libraries=geometry&callback=googleMapsAPILoaded`;
      document.body.appendChild(element);
    }
  });

/*
 *   Styles for the map.
 */

const MAP_STYLES = [
  {
    elementType: "labels",
    stylers: [
      {
        visibility: "off",
      },
    ],
  },
  {
    featureType: "administrative.land_parcel",
    elementType: "labels",
    stylers: [
      {
        visibility: "on",
      },
    ],
  },
  {
    featureType: "administrative.locality",
    elementType: "labels",
    stylers: [
      {
        visibility: "on",
      },
    ],
  },
  {
    featureType: "administrative.neighborhood",
    elementType: "labels",
    stylers: [
      {
        visibility: "on",
      },
    ],
  },
];

/*
 *   React component for the map of conversations.
 */

const logger = console;
export default class ConversationMap extends React.Component {
  constructor(props) {
    super(props);
    this.state = {};

    const { selectedMarker } = props;
    if (selectedMarker) {
      this.state.selectedMarker = selectedMarker;
    }

    this.createMap = this.createMap.bind(this);
    this.addMarkers = this.addMarkers.bind(this);
    this.handleSelectedMarker = this.handleSelectedMarker.bind(this);
    this.findNearestMarkers = this.findNearestMarkers.bind(this);
    this.clusterMarkers = this.clusterMarkers.bind(this);
  }

  /*
   *   Creates the map as soon as the DOM element is ready.
   */

  createMap(element) {
    if (element) {
      loadGoogleMapsAPI()
        .then(() => {
          this.map = new window.google.maps.Map(element, {
            zoom: 2,
            center: new window.google.maps.LatLng(19.1724, -41.0266),
            streetViewControl: false,
            mapTypeControl: false,
            fullscreenControl: true,
          });

          const styledMapType = new window.google.maps.StyledMapType(
            MAP_STYLES,
            {
              name: "Styled Map",
            }
          );
          this.map.mapTypes.set("styled_map", styledMapType);
          this.map.setMapTypeId("styled_map");
          this.infoWindow = new window.google.maps.InfoWindow();
        })
        .then(() => downloadConversations({ for: "mapOnly" }))
        .then(({ conversations }) => this.addMarkers(conversations))
        .then(this.handleSelectedMarker)
        .catch(logger.error.bind(console));
    }
  }

  /*
   *   Adds markers to the map from Conversation data.
   */

  addMarkers(conversations) {
    const markers = conversations.map((conversation) => {
      const position = new window.google.maps.LatLng(
        conversation.location.latitude,
        conversation.location.longitude
      );
      const normalIcon = {
        url: markerIcon,
        scaledSize: { width: 32, height: 51 },
      };

      const selectedIcon = {
        url: selectedMarkerIcon,
        scaledSize: { width: 48, height: 75 },
      };

      const infoWindowContent = `
                <a href="${conversation.link}" target="_blank" rel="noopener noreferrer" class="has-text-grey-darker">
                    <div class="map-popup-title">${conversation.place_name}</div>
                    <div class="map-popup-link">Click for conversation</div>
                </a>
            `;

      const mapMarker = new window.google.maps.Marker({
        position,
        icon: normalIcon,
      });

      const { miniMap } = this.props;
      if (!miniMap) {
        mapMarker.addListener("click", () => {
          this.infoWindow.setContent(infoWindowContent);
          this.infoWindow.open(this.map, mapMarker);
          Object.values(this.markers).forEach((marker) =>
            marker.mapMarker.setIcon(normalIcon)
          );
          mapMarker.setIcon(selectedIcon);
        });
      }

      mapMarker.setMap(this.map);
      return {
        id: conversation.id,
        position,
        mapMarker,
        infoWindowContent,
        name: conversation.place_name,
      };
    });

    this.markers = {};
    markers.forEach(({ id, position, mapMarker, infoWindowContent, name }) => {
      this.markers[id] = { position, mapMarker, infoWindowContent, name };
    });
  }

  /*
   *   Zooms into and opens the popup for the selected marker.
   */

  handleSelectedMarker() {
    const { selectedMarker } = this.state;
    if (selectedMarker && this.markers[selectedMarker]) {
      const { position, mapMarker, infoWindowContent } = this.markers[
        selectedMarker
      ];

      //  Makes selected marker larger
      const selectedIcon = {
        url: selectedMarkerIcon,
        scaledSize: { width: 48, height: 75 },
      };

      mapMarker.setIcon(selectedIcon);

      //  Zooms into the mapMarker and closest other 2 mapMarkers
      const [
        originalMarker,
        closestMarker,
        secondClosestMarker,
      ] = this.findNearestMarkers(position, 3);
      const bounds = new window.google.maps.LatLngBounds()
        .extend(originalMarker.position)
        .extend(closestMarker.position)
        .extend(secondClosestMarker.position);

      this.map.fitBounds(bounds, 50);

      //  Opens info window and sets up marker clustering if full-size map
      const { miniMap } = this.props;
      if (!miniMap) {
        this.infoWindow.setContent(infoWindowContent);
        this.infoWindow.open(this.map, mapMarker);
        let listener;
        setTimeout(() => {
          listener = this.map.addListener("bounds_changed", () => {
            this.clusterMarkers();
            window.google.maps.event.removeListener(listener);
          });
        }, 500);
      } else
        this.map.setOptions({ gestureHandling: "none", zoomControl: false });
    } else this.clusterMarkers();
  }

  /*
   *   Orders markers by distance to a marker, returning the closest two.
   */

  findNearestMarkers(position, nClosest) {
    const markers = Object.values(this.markers);
    const sortedMarkers = markers.sort((a, b) => {
      const dA = window.google.maps.geometry.spherical.computeDistanceBetween(
        position,
        a.position
      );
      const dB = window.google.maps.geometry.spherical.computeDistanceBetween(
        position,
        b.position
      );
      return dA - dB;
    });

    return sortedMarkers.slice(0, nClosest);
  }

  /*
   *   Clusters the markers with the MarkerClustererPlus library.
   */

  clusterMarkers() {
    const mapMarkers = Object.values(this.markers).map(
      ({ mapMarker }) => mapMarker
    );
    mapMarkers.forEach((mapMarker) => mapMarker.setMap(null));
    // eslint-disable-next-line
    new window.MarkerClusterer(this.map, mapMarkers, {
      styles: [
        {
          url: markerGroupIcon,
          height: 56,
          width: 42,
          textColor: "#fff",
          textSize: 13,
          anchorText: [-3, -1],
        },
      ],
      enableRetinaIcons: true,
    });
  }

  render() {
    const { height, miniMap } = this.props;
    return (
      <React.Fragment>
        <div className="map-wrapper" style={{ height }} ref={this.createMap} />
        {miniMap ? (
          <div className="map-subtitle">
            Click map to reveal this conversation on the full-size map and see
            nearby conversations.
          </div>
        ) : (
          <div className="map-subtitle">
            Click a marker group <img src={markerGroupIcon} alt="" /> to zoom in
            on the map and explore conversations.
          </div>
        )}
      </React.Fragment>
    );
  }
}

ConversationMap.propTypes = {
  selectedMarker: number,
  miniMap: bool,
  height: string,
};

ConversationMap.defaultProps = {
  selectedMarker: null,
  miniMap: false,
  height: "35em",
};
