<?php

defined('ABSPATH') || exit();

/**
 * Styles for mobile hamburguer menu
 */

// Menu size
switch ( $json_settings['mobile_ham_size'] ) {
    case 'small':
        $css .= '
            body button.uicore-ham{
                transform: scale(0.8);
            }
        ';
        break;

    case 'large':
        $css .= '
            body button.uicore-ham{
                transform: scale(1.2);
            }
        ';
        break;
}

// Menu Styles
switch($json_settings['mobile_ham_icon']) {
    case 'default':
        $css .= '
            body:not(.uicore-mobile-nav-show) .uicore-ham-default .bars{
                transform: none;
                transition-delay: 0s;
            }

            body:not(.uicore-mobile-nav-show) .uicore-ham-default .bar:first-child,
            body:not(.uicore-mobile-nav-show) .uicore-ham-default .bar:nth-child(2),
            body:not(.uicore-mobile-nav-show) .uicore-ham-default .bar:last-child {
                opacity: 1;
                visibility: visible;
                transform: none;
                transition: all 0.3s ease,
                            background-color 0.15s;
            }
            body:not(.uicore-mobile-nav-show) .uicore-ham-default .bar:first-child:before,
            body:not(.uicore-mobile-nav-show) .uicore-ham-default .bar:first-child:after,
            body:not(.uicore-mobile-nav-show) .uicore-ham-default .bar:nth-child(2):before,
            body:not(.uicore-mobile-nav-show) .uicore-ham-default .bar:nth-child(2):after,
            body:not(.uicore-mobile-nav-show) .uicore-ham-default .bar:last-child:before,
            body:not(.uicore-mobile-nav-show) .uicore-ham-default .bar:last-child:after {
                transform: rotate(0) translate(0, 0);
            }
            body:not(.uicore-mobile-nav-show) .uicore-ham-default .bar:first-child,
            body:not(.uicore-mobile-nav-show) .uicore-ham-default .bar:last-child {
                transition-delay: 0.15s;
            }
            body:not(.uicore-mobile-nav-show) .uicore-ham-default .bar:nth-child(2) {
                width: 16px;
            }
            body .uicore-ham-default .bars {
                transform: rotate(0);
                transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
            }
            body .uicore-ham-default .bar{
                transition: all 0.45s cubic-bezier(0.23, 1, 0.32, 1);
                border-radius: 50em;
                margin-bottom: 4.4px;
                position: relative;
            }
            body .uicore-ham-default .bar:first-child,
            body .uicore-ham-default .bar:last-child {
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
            }
            body .uicore-ham-default .bar:first-child {
                transform: translate3d(0,-3px,0);
            }
            body .uicore-ham-default .bar:last-child {
                margin-bottom: 0;
                transform: translate3d(0,3px,0);
            }
            body .uicore-ham-default .bar:nth-child(2) {
                margin-right: auto;
                transform: rotate(45deg);
                transition-delay: 0.1s;
                transition-timing-function: cubic-bezier(0.23, 1, 0.32, 1);
            }
            body .uicore-ham-default .bar:nth-child(2):before {
                content: "";
                display: inline-block;
                width: 100%;
                height: 100%;
                position: absolute;
                top: 0;
                left: 0;
                border-radius: inherit;
                transition: inherit;
                background-color: inherit;
                transform: rotate(-90deg);
            }
        ';
        break;

    case 'classic':
            $css .= '
                body:not(.uicore-mobile-nav-show) .uicore-ham-classic .bar:first-child {
                    transform: rotate(0deg);
                }
                body:not(.uicore-mobile-nav-show) .uicore-ham-classic .bar:nth-child(2) {
                    opacity: 1;
                    width: 20px;
                }
                body:not(.uicore-mobile-nav-show) .uicore-ham-classic .bar:last-child {
                    transform: rotate(0deg);
                }
                body:not(.uicore-mobile-nav-show) .uicore-ham-classic .bars{
                    transition-delay: 0.2s;
                    transition: justify-content .1s ease;
                }

                body .uicore-ham-classic .bars{
                    position: relative;
                    justify-content: space-between;
                    align-items: start;
                }
                body .uicore-ham-classic .bar {
                    border-radius: 50em;
                    transform-origin: left;
                    transition: transform .5s ease,
                                opacity .6s ease,
                                width .3s ease;
                }
                body .uicore-ham-classic .bar:first-child {
                    transform: rotate(45deg);
                }
                body .uicore-ham-classic .bar:nth-child(2) {
                    opacity: 0;
                    width: 0px;
                }
                body .uicore-ham-classic .bar:last-child {
                    transform: rotate(-45deg);
                }
            ';
        break;

    case 'minimalist':
        $css .= '
            body:not(.uicore-mobile-nav-show) .uicore-ham-minimalist .bar:first-child,
            body:not(.uicore-mobile-nav-show) .uicore-ham-minimalist .bar:last-child {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
            body:not(.uicore-mobile-nav-show) .uicore-ham-minimalist .bar:nth-child(2) {
                opacity: 0;
                transform: rotate(45deg) scale(.6);
            }
            body:not(.uicore-mobile-nav-show) .uicore-ham-minimalist .bar:nth-child(2) {
                transition: transform .3s 0 ease,
                            opacity .3s 0 ease;
            }
            body:not(.uicore-mobile-nav-show) .uicore-ham-minimalist .bar:first-child,
            body:not(.uicore-mobile-nav-show) .uicore-ham-minimalist .bar:last-child {
                transition: transform .3s .1s ease,
                            opacity .3s .1s ease;
            }

            body .uicore-ham-minimalist .bars{
                position: relative;
                height: 12px;
                width: 16px;
                justify-content: space-evenly;
                align-items: start;
            }
            body .uicore-ham-minimalist .bar:first-child,
            body .uicore-ham-minimalist .bar:last-child {
                border-radius: 50em;
                width: 16px;
                transition: transform .3s ease,
                            opacity .3s ease;
            }
            body .uicore-ham-minimalist .bar:first-child {
                opacity: 0;
                transform: translate3d(0, 4px, 0);
            }
            body .uicore-ham-minimalist .bar:last-child {
                opacity: 0;
                transform: translate3d(0, -4px, 0);
            }
            body .uicore-ham-minimalist .bar:nth-child(2) {
                opacity: 1;
                width: 16px;
                transform-origin: center;
                transform: rotate(45deg) scale(1);
                transition: transform .3s .1s ease,
                            opacity .3s .1s ease;
            }
            body .uicore-ham-minimalist .bar:nth-child(2):before {
                content: "";
                display: inline-block;
                width: 100%;
                height: 100%;
                position: absolute;
                top: 0;
                left: 0;
                border-radius: inherit;
                background-color: inherit;
                transform: rotate(-90deg);
            }
        ';
        break;

    case 'creative':
            $css .= '
                body:not(.uicore-mobile-nav-show) .uicore-ham-creative .bar:first-child,
                body:not(.uicore-mobile-nav-show) .uicore-ham-creative .bar:nth-child(2),
                body:not(.uicore-mobile-nav-show) .uicore-ham-creative .bar:last-child {
                    transform: rotate(0deg);
                }

                body .uicore-ham-creative .bars {
                    display: flex;
                    justify-content: space-around;
                }
                body .uicore-ham-creative .bar {
                    transform-origin: center;
                    transition: transform 0.5s cubic-bezier( 0.895, 0.03, 0.685, 0.22 );
                }

                body .uicore-ham-creative .bar:first-child {
                    align-self: baseline;
                    width: 10px;
                    transform: translate3d(1px, 1px, 0px) rotateZ(45deg);
                }

                body .uicore-ham-creative .bar:nth-child(2) {
                    transform: translate3d(0px, 0px, 0px) rotateZ(-45deg);
                }

                body .uicore-ham-creative .bar:last-child {
                    width: 10px;
                    align-self: flex-end;
                    transform: translate3d(-1px, -1px, 0px) rotateZ(45deg);

                }
            ';
        break;

    case 'text':
        $css .= '
            body:not(.uicore-mobile-nav-show) .uicore-ham-text span.ui-ham-open {
                opacity: 1;
                filter: blur(0);
            }

            body:not(.uicore-mobile-nav-show) .uicore-ham-text span.ui-ham-close {
                opacity: 0;
                filter: blur(2px);
            }

            body button.uicore-ham-text{
                padding-left: 0px !important;
            }

            body .uicore-ham-text {
                position: relative;
                width: fit-content !important;
            }

            body .uicore-ham-text span {
                transition: opacity 0.3s ease,
                filter 0.2s ease;
                font-size: 14px;
                color: inherit;
                font-weight: inherit;
                line-height: var(--uicore-header--logo-h);
                white-space: nowrap;
            }

            body .uicore-ham-text span.ui-ham-open {
                opacity: 0;
                filter: blur(2px);
            }

            body .uicore-ham-text span.ui-ham-close {
                position: absolute;
                top: 0;
                right: 0;
                opacity: 1;
                filter: blur(0);
            }
        ';
        break;
}

