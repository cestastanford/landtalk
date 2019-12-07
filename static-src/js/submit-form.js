/*
*   Imports.
*/

import debounce from 'lodash.debounce';

import { loadGoogleMapsAPI } from './conversation-map';
import { getValidYouTubeId } from './rest';
import { GOOGLE_MAPS_JS_API_KEY } from '../.env';
import selectedMarkerIcon from '../img/selected-marker.png';


/*
*   Adds a Google Maps embed with the selected location.
*/

const setupLocationPreview = () => {
  loadGoogleMapsAPI().then(() => {
    const latitude = document.querySelector('#submit-form-latitude input');
    const longitude = document.querySelector('#submit-form-longitude input');
    const locationPreview = document.querySelector('#submit-form-location-preview');
    locationPreview.innerHTML = `
      <div id="submit-form-location-preview-map"></div>
      <div id="submit-form-location-preview-title">Enter coordinates to preview location.</div>
    `;

    const locationPreviewMap = locationPreview.querySelector('#submit-form-location-preview-map');
    const locationPreviewTitle = locationPreview.querySelector('#submit-form-location-preview-title');
    const map = new window.google.maps.Map(locationPreviewMap, {
      zoom: 1,
      center: new window.google.maps.LatLng(39.8282, -98.5795),
      gestureHandling: 'none',
      streetViewControl: false,
      mapTypeControl: false,
      fullscreenControl: false,
    });

    const selectedIcon = {
      url: selectedMarkerIcon,
      scaledSize: { width: 48, height: 75 },
    };

    const marker = new window.google.maps.Marker({
      position: { lat: 0, lng: 0 },
      icon: selectedIcon,
    });

    const handleInput = debounce(() => {
      let lat;
      let lng;
      if (latitude.value && +latitude.value && +latitude.value > -90 && +latitude.value < 90) {
        lat = +latitude.value;
      }

      if (
        longitude.value
        && +longitude.value
        && +longitude.value > -180
        && +longitude.value < 180
      ) {
        lng = +longitude.value;
      }

      if (lat && lng) {
        locationPreviewTitle.innerText = 'Searching...';
        fetch(`https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${GOOGLE_MAPS_JS_API_KEY}`)
          .then((response) => response.json())
          .then((parsedResponse) => {
            if (parsedResponse.status === 'ZERO_RESULTS') {
              locationPreviewTitle.innerHTML = '<strong class="has-text-danger">No nearby locations found.</strong>';
            } else locationPreviewTitle.innerText = parsedResponse.results[0].formatted_address;
          });

        map.setCenter({ lat, lng });
        map.setZoom(10);
        marker.setPosition({ lat, lng });
        marker.setMap(map);
      }
    }, 500);

    latitude.addEventListener('input', handleInput);
    longitude.addEventListener('input', handleInput);
  });
};


/*
*   Adds a preview of the YouTube video.
*/

const setupVideoPreview = () => {
  const urlInput = document.querySelector('#submit-form-youtube-url input');
  const youtubePreview = document.querySelector('#submit-form-youtube-preview');
  youtubePreview.innerHTML = `
    <div id="submit-form-youtube-preview-embed"></div>
    <div id="submit-form-youtube-preview-title">Enter URL to preview video.</div>
  `;

  const youtubePreviewEmbed = youtubePreview.querySelector('#submit-form-youtube-preview-embed');
  const youtubePreviewTitle = youtubePreview.querySelector('#submit-form-youtube-preview-title');
  const handleInput = debounce((event) => {
    const url = event.target.value;
    if (url) {
      youtubePreviewTitle.innerHTML = '<em>Checking...<em>';
      getValidYouTubeId(url).then((id) => {
        if (id) {
          youtubePreviewEmbed.innerHTML = `<iframe width="100%" height="100%" src="https://www.youtube.com/embed/${id}?rel=0&amp;showinfo=0" frameborder="0" gesture="media" allow="encrypted-media" allowfullscreen></iframe>`;
          youtubePreviewTitle.innerHTML = '<span class="has-text-success">Valid YouTube URL detected.</span>';
        } else {
          youtubePreviewEmbed.innerHTML = '';
          youtubePreviewTitle.innerHTML = '<strong class="has-text-danger">No valid YouTube URL detected.</strong>';
        }
      });
    }
  }, 500);

  urlInput.addEventListener('input', handleInput);
};


/*
*   Stops ACF from saving accordion position, so Google Maps embed
*   correctly loads.
*/

const clearAccordions = () => {
  window.jQuery(window).off('unload');
  localStorage.setItem('acf', '');
};


export default () => {
  setupLocationPreview();
  setupVideoPreview();
  clearAccordions();
};
