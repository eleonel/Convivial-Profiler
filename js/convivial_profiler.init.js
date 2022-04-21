/**
 * @file
 * Convivial Profiler library init.
 *
 * Copyright Morpht Pty Ltd 2020.
 * This code must be licensed.
 */

(function ($, window , once, Drupal, config, ConvivialProfiler) {

  'use strict';
  Drupal.behaviors.convivialProfiler = {
    attach: function (context, settings) {
       // @Todo Due of conflict in tracking of smart_content ajax blocks.
      // See the formfiller and formtracker destination plugins.
      // once('convivialProfiler', 'html', context).forEach( function (element) {
        window.convivialProfiler = new ConvivialProfiler(config.config, config.site, config.license_key);
        window.convivialProfiler.collect();
        $(document).once('cp_trackable').on('click', '.cp_trackable a.btn', function (event) {
          if (config.event_tracking) {
            event.preventDefault();
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
              event: 'convivialProfiler.event',
              category: $(this).parents('.cp_trackable').first().attr("data-cp-category"),
              action: "click",
              label: $(this).parents('.cp_trackable').first().attr("data-cp-label"),
              eventCallback: function () {
                window.location = event.target.href;
              }
            });
          }
        });
      // });
    }
  };
})(jQuery, window, once, Drupal, drupalSettings.convivialProfiler, window.ConvivialProfiler.default);
