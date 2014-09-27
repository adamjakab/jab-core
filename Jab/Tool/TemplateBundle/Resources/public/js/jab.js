/*! Jab Main Module */
/**
 * This file is always included
 * Any modules required in here will be loaded before the actual page specific module is loaded
 * So these are global dependencies.
 * Implementation in twig:
 * require(["jab"], function(jab) {
 *   require(['{{ requirejs_module }}']);
 * });
 */
requirejs.config({
    /*baseUrl: '/assets/js', - already set in requirejs.html.twig */
    paths: {
        /* Directory Mappings */
        bundles: '../../bundles',
        vendor: '../vendor',
        /*File Mappings (above declared Dir mappings cannot be used in here)*/
        /*VENDOR*/
        domReady: '../vendor/requirejs-domready/domReady',
        jquery: '../vendor/jquery/dist/jquery',
        require: '../vendor/requirejs/require',
        bootstrap: '../vendor/bootstrap/dist/js/bootstrap.min'
    },
    shim: {
        bootstrap: {deps:['jquery']}
    },
    deps: ['bootstrap']/*add global dependencies*/
});


define(['require'], function(require) {
    'use strict';
    require(['domReady!'], function (document) {
        //init something
    });
});

