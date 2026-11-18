/* The public site's entry (layouts/public, 2026-11-18 PageSpeed): the app's own core without resources/js/bootstrap.js.
   Axios, Laravel Echo and Pusher serve the Collab Room and the app's realtime only, and they were about a quarter of
   the bundle every phone downloaded beside the home page's hero. No public page uses them. */
import './phone-camera';
import './site-events';
import './core';
