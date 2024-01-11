@extends('layouts.app')

@push('styles')
  <style>
    /* #map { */
    /* background-color: greenyellow; */
    /* opacity: 0.5; */
    /* } */

    /* .leaflet-tile { */
    /* border: solid greenyellow 2px; */
    /* border: solid white 2px; */
    /* opacity: 0.8!important; */
    /* } */
    .overlay {
      position: fixed;
      /* Sit on top of the page content */
      display: none;
      /* Hidden by default */
      width: 100%;
      /* Full width (cover the whole page) */
      height: 100%;
      /* Full height (cover the whole page) */
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      /* Black background with opacity */
      z-index: 100000;
      /* Specify a stack order in case you're using a different order for other elements */
      cursor: pointer;
      /* Add a pointer on hover */
    }
  </style>
@endpush

@section('content')
  <div id="map"></div>
  <div class="overlay"></div>
  <div class="position-relative">
    <div class="position-absolute bottom-0 start-0 px-2 pb-4" style="z-index:9999;">
      <div class="row">
        <div class="col-1"></div>
        <div class="col-3">
          <button id="btnPetaRute" type="button" class="btn btn-md btn-light border border-dark shadow-lg rounded"
            style=""><span class="tgglPetaRute">Tampilkan</span> Peta Rute TransJakarta</button>
        </div>
        <div class="col-3">
          <button id="btnHalte" type="button" class="btn btn-md btn-light border border-dark shadow-lg rounded"
            style=""><span class="tgglHalte">Tampilkan</span> Halte TransJakarta</button>
        </div>
        <div class="col-3">
          <button id="btnRute" type="button" class="btn btn-md btn-light border border-dark shadow-lg rounded"
            style="width:20ch;"><span class="tgglRute">Cari Rute</span></button>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  {{-- <script src="{{ asset('js/browserify/bundle.js') }}"></script> --}}
  <script type="text/javascript">
    'use strict';
    const State = {
      isPanning: false,
      btnPetaRuteClicked: false,
      btnHalteClicked: false,
      stateBtnRute: 0, // 0->find_route, 1->start_loc, 2->end_loc, 3->reset
    };

    const Wrapper = {
      activate: function() {
        Wrapper.init();
        Lf.activate();
        Menu.activate();
      },
      init: function() {},
    };

    const Menu = {
      activate: function() {
        Menu.event();
      },
      event: function() {
        document.getElementById('btnPetaRute').addEventListener('click', function(e) {
          let btn = e.target;
          if (!State.btnPetaRuteClicked) {
            State.btnPetaRuteClicked = true;
            document.getElementsByClassName('tgglPetaRute')[0].innerText = 'Sembunyikan';
            Lf.addBaseTransJakartaLine();
          } else {
            State.btnPetaRuteClicked = false;
            document.getElementsByClassName('tgglPetaRute')[0].innerText = 'Tampilkan';
            Lf.removeBaseTransJakartaLine();
          }
        });
        document.getElementById('btnHalte').addEventListener('click', function(e) {
          let btn = e.target;
          if (!State.btnHalteClicked) {
            State.btnHalteClicked = true;
            document.getElementsByClassName('tgglHalte')[0].innerText = 'Sembunyikan';
            Lf.addHalteTransjakarta();
          } else {
            State.btnHalteClicked = false;
            document.getElementsByClassName('tgglHalte')[0].innerText = 'Tampilkan';
            Lf.removeHalteTransjakarta();
          }
        });
        document.getElementById('btnRute').addEventListener('click', function(e) {
          let btn = e.target;
          switch (State.stateBtnRute) {
            case 0: // find_route
              State.stateBtnRute = 1; // next to start_loc
              document.getElementsByClassName('tgglRute')[0].innerText = 'Tentukan Lokasi Saat ini';
              break;
            case 1: // start_loc
              State.stateBtnRute = 2; // next to end_loc
              document.getElementsByClassName('tgglRute')[0].innerText = 'Tentukan Tujuan';
              break;
            case 2: // end_loc
              State.stateBtnRute = 3; // next to reset
              document.getElementsByClassName('tgglRute')[0].innerText = 'Bersihkan Rute';
              break;
            case 3: // reset
              State.stateBtnRute = 0; // next to find_route
              Lf.removeRouteTransJakartaLine();
              Lf.removeMarkerStart();
              Lf.removeMarkerEnd();
              document.getElementsByClassName('tgglRute')[0].innerText = 'Cari Rute';
              break;
            default:
              State.stateBtnRute = 3; // next to reset
              break;
          }
        });
      },
    }

    const API = {
      baseTransJakartaLine: function() {
        return new Promise(async (resolve, reject) => {
          try {
            const response = await fetch("{{ asset('features/jalur_koridor_transjakarta.geojson') }}");
            resolve(response.json());
          } catch (err) {
            console.error(err);
            reject('cannot get resource transjakarta line');
          }
        });
      },
      halteTransJakarta: function() {
        return new Promise(async (resolve, reject) => {
          try {
            const response = await fetch("{{ asset('features/halte_transjakarta.geojson') }}");
            resolve(response.json());
          } catch (err) {
            console.error(err);
            reject('cannot get resource transjakarta line');
          }
        });
      },
      routeTransJakartaLine: function({
        start,
        end
      }) {
        return new Promise(async (resolve, reject) => {
          try {
            const response = await fetch("{{ route('api.transjakarta.route') }}", {
              method: 'POST',
              mode: "cors", // no-cors, *cors, same-origin
              cache: "no-cache", // *default, no-cache, reload, force-cache, only-if-cached
              credentials: "same-origin", // include, *same-origin, omit
              headers: {
                "Content-Type": "application/json",
                // 'Content-Type': 'application/x-www-form-urlencoded',
                "X-CSRF-TOKEN": document.querySelectorAll('meta[name="csrf-token"]')[0].getAttribute(
                  'content'),
              },
              redirect: "error", // manual, *follow, error
              referrerPolicy: "no-referrer", // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
              body: JSON.stringify({
                start,
                end
              }),
            });
            resolve(response.json());
          } catch (err) {
            console.error(err);
            reject('cannot get resource transjakarta line');
          }
        });
      },
    }

    const Icon = {
      // blueIcon: L.icon({
      //   iconUrl: "{{ asset('icons/red-icon.png') }}"
      // }),
      redIcon: L.icon({
        iconUrl: "{{ asset('icons/red-icon.png') }}",
        iconSize: [38, 43],
        iconAnchor: [22, 50],
        popupAnchor: [-3, -76],
      }),
    }

    // ## centering + zoom
    // Lf.map.fitBounds([
    //     [40.712, -74.227],
    //     [40.774, -74.125]
    // ]);
    // ## centering with animation
    // Lf.map.panTo([40.712, -74.227], { duration: 5 });
    // Lf mean Leaflet
    const Lf = {
      map: null,
      activate: async function() {
        // Lf.map = L.map('map').setView([-1.38116, 117.6168817], 5.4); // centering indonesia country
        // Lf.map = L.map('map').setView([-7.1451449, 109.9776078], 8); // centering javanese province
        // Lf.map = L.map('map').setView([-7.8241413, 112.9071746], 9); // centering east java province
        Lf.map = L.map('map', {
          zoomControl: false,
        });
        Lf.map.setView([-6.2178534, 106.8341076], 12); // map panning on jakarta city

        // tileLayerReference
        // https://openmaptiles.org/styles/
        // https://gis.stackexchange.com/questions/423609/where-are-osm-tile-servers-corresponding-to-its-supported-layers
        // https://wiki.openstreetmap.org/wiki/Raster_tile_providers
        Lf.tileLayerGoogleStreets();
        // Lf.debugCoords();

        // global controls
        L.control.zoom({
          position: 'bottomright'
        }).addTo(Lf.map);
        L.control.scale({
          position: 'topright'
        }).addTo(Lf.map);

        // events
        Lf.events();
      },
      tileLayerOSM: function() {
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png?{foo}', {
          foo: 'bar',
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
      },
      tileLayerGoogleStreets: function() {
        // https://stackoverflow.com/questions/9394190/Lf-map-api-with-google-satellite-layer
        L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
          maxZoom: 20,
          subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
          detectRetina: true,
          attribution: '&copy; <a href="https://www.google.com/maps">ThanksToGoogleMap</a>',
        }).addTo(Lf.map);
      },
      tileLayerGoogleHybrid: function() {
        L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
          maxZoom: 20,
          subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
          detectRetina: true,
          attribution: '&copy; <a href="https://www.google.com/maps">ThanksToGoogleMap</a>',
        }).addTo(Lf.map);
      },
      tileLayerGoogleSatellite: function() {
        L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
          maxZoom: 20,
          subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
          detectRetina: true,
          attribution: '&copy; <a href="https://www.google.com/maps">ThanksToGoogleMap</a>',
        }).addTo(Lf.map);
      },
      tileLayerGoogleTerrain: function() {
        L.tileLayer('http://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}', {
          maxZoom: 20,
          subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
          detectRetina: true,
          attribution: '&copy; <a href="https://www.google.com/maps">ThanksToGoogleMap</a>',
        }).addTo(Lf.map);
      },
      debugCoords: function() {
        // https://leafletjs.com/examples/extending/extending-2-layers.html
        // https://stackoverflow.com/questions/56669940/how-to-make-interactive-leaflet-tiles-from-gridlayer
        // https://plnkr.co/edit/7BQS4w31hjKLipAL379D?p=preview&preview
        let grid = L.gridLayer();
        grid.createTile = function(coords) {
          var tile = document.createElement('div');
          tile.innerHTML = [coords.x, coords.y, coords.z].join(', ');
          tile.style.outline = '1px solid red';
          return tile;
        }
        Lf.map.addLayer(grid); // grid.addTo(Lf.map) // it same
      },
      addBaseTransJakartaLine: async function() {
        let geojsonFeature = await API.baseTransJakartaLine();
        let style = {
          onEachFeature: function onEachFeature(feature, markerLayer) {
            if (feature.properties && feature.properties.name) {
              markerLayer.bindPopup('<b>' + feature.properties.name + '</b>');
            }
          }
        };
        Lf.layerBaseTransJakartaLine = L.geoJSON(geojsonFeature, style).addTo(Lf.map);
      },
      removeBaseTransJakartaLine: async function() {
        Lf.map.removeLayer(Lf.layerBaseTransJakartaLine);
      },
      addHalteTransjakarta: async function() {
        let geojsonFeature = await API.halteTransJakarta();
        let style = {
          pointToLayer: function(feature, latlng) {
            let geojsonMarkerOptions = {
              radius: 4,
              fillColor: "#ff7800",
              color: "#000",
              weight: 1,
              opacity: 1,
              fillOpacity: 0.8
            };
            return L.circleMarker(latlng, geojsonMarkerOptions);
          },
          onEachFeature: function onEachFeature(feature, markerLayer) {
            if (feature.properties && feature.properties.Name) {
              markerLayer.bindPopup('<b>' + feature.properties.Name + '</b><br>' + feature.properties.Snippet);
            }
          }
        }
        Lf.layerHalteTransjakarta = L.geoJSON(geojsonFeature, style).addTo(Lf.map);
      },
      removeHalteTransjakarta: async function() {
        Lf.map.removeLayer(Lf.layerHalteTransjakarta);
      },
      addRouteTransJakartaLine: async function(payload) {
        let route = await API.routeTransJakartaLine(payload);
        if (!route.meta.status) return;
        // https://github.com/mapbox/wellknown?tab=readme-ov-file
        // https://github.com/browserify/browserify?tab=readme-ov-file
        // let latlngs = parse(route.data.geom);
        let style = {
          color: "red",
          onEachFeature: function onEachFeature(feature, markerLayer) {
            if (feature.properties && feature.properties.name) {
              markerLayer.bindPopup('<b>' + feature.properties.name + '</b>');
            }
          }
        };
        Lf.ruteBus = L.geoJSON(route.data, style).addTo(Lf.map);
      },
      removeRouteTransJakartaLine: async function() {
        Lf.map.removeLayer(Lf.ruteBus);
      },
      addMarkerStart: async function([lat, lng]) {
        let opt = {
          // icon: Icon.blueIcon,
        };
        Lf.markerStart = L.marker([lat, lng], opt).addTo(Lf.map);
      },
      removeMarkerStart: async function() {
        if (Lf.markerStart == null || Lf.markerStart == '' || typeof Lf.markerStart == 'undefined') return true;
        Lf.map.removeLayer(Lf.markerStart);
      },
      addMarkerEnd: async function([lat, lng]) {
        let opt = {
          icon: Icon.redIcon,
          alt: '<a href="https://www.flaticon.com/free-icons/pin" title="pin icons">Pin icons created by Freepik - Flaticon</a>',
        };
        Lf.markerEnd = L.marker([lat, lng], opt).addTo(Lf.map);
      },
      removeMarkerEnd: async function() {
        if (Lf.markerEnd == null || Lf.markerEnd == '' || typeof Lf.markerEnd == 'undefined') return true;
        Lf.map.removeLayer(Lf.markerEnd);
      },
      events: function() {
        let payload = {
          start: {
            lat: -6.308167,
            lon: 106.821490,
          },
          end: {
            lat: -6.124769,
            lon: 106.830541,
          },
        };
        Lf.map.on('click', function(e) {
          // console.log(e.latlng.lat, e.latlng.lng);
          switch (State.stateBtnRute) {
            case 1: // start_loc
              payload.start = {
                lat: e.latlng.lat,
                lon: e.latlng.lng
              };
              document.getElementById('btnRute').click();
              Lf.addMarkerStart([e.latlng.lat, e.latlng.lng]);
              break;
            case 2: // end_loc
              payload.end = {
                lat: e.latlng.lat,
                lon: e.latlng.lng
              };
              Lf.addRouteTransJakartaLine(payload);
              document.getElementById('btnRute').click();
              Lf.addMarkerEnd([e.latlng.lat, e.latlng.lng]);
              payload = {};
              break;
          }
        });
        // https://gis.stackexchange.com/questions/104507/disable-panning-dragging-on-Lf-map-for-div-within-map // number 2 from bottom
        // panning is freezes a moving object
        // $('.disablePanning').on('mousedown', function() {
        //   if (State.isPanning) {
        //     State.isPanning = false;
        //     Lf.map.dragging.disable();
        //   }
        // });
        // $('.disablePanning').on('mouseup', function() {
        //   if (!State.isPanning) {
        //     State.isPanning = true;
        //     Lf.map.dragging.enable();
        //   }
        // });
      },
    };

    Wrapper.activate();
  </script>
@endpush
