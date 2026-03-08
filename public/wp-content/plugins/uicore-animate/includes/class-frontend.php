<?php

namespace UiCoreAnimate;


/**
 * Frontend Pages Handler
 */
class Frontend
{

    private $style = '';

    /**
     * Constructor function to initialize hooks
     *
     * @return void
     */

    public function __construct()
    {
        //Handle animation style in UiCore Framework Global if is active
        if (!\class_exists('\UiCore\Helper')) {
            $style = Settings::get_option('uianim_style');
            if (is_array($style)) {
                $this->style = $style['value'] ? $style['value'] : 'style1'; //fallback for default style
            } else {
                $this->style = 'style1';
            }

            if ($this->style) {
                add_action('elementor/frontend/after_enqueue_scripts', function () {
                    wp_deregister_style('e-animations');
                    wp_dequeue_style('e-animations');
                }, 20);

                add_action('wp_enqueue_scripts', [$this, 'remove_elementor_animation_style'], 60);

                // Defer the loading to footer by adding inline style in the footer
                add_action('get_footer', function () {
                    wp_enqueue_style('uianim-style', UICORE_ANIMATE_ASSETS . '/css/' . $this->style . '.css');
                }, 1000);
            }

            //scroll
            add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 60);
        } else {
            //add the resources to global files in UiCore Framework
            add_filter('uicore_css_global_files', [$this, 'add_css_to_framework'], 10, 2);
            add_filter('uicore_js_global_files', [$this, 'add_js_to_framework'], 10, 2);
        }

        \add_action('wp_head', [$this, 'add_scroll_timeline_polyfill'], 999);

        if (is_plugin_active('gtranslate/gtranslate.php')) {
            add_filter('script_loader_tag', [$this, 'gtranslate_fix'], 20, 3);
        }
    }

    /**
     * Fix GTranslate conflict with splitted text (by chars) by
     * unsplitting the texts before translation happens.
     */
    public static function gtranslate_fix($tag, $handle, $src)
    {
        static $script_added = false;

        if (!empty($src) and strpos($handle, 'gt_widget_script_') === 0) {

            // Check if cookie uicore_gtranslate_fix is set
            if (!$script_added) {
                $script_added = true;
                $tag = $tag
                    // TODO: highlighted text loses both spacing and highligh stroke
                    // also, not working when the page already loads in a different language
                    . '<script>
                        const scripts = document.querySelectorAll(\'script[data-gt-widget-id]\');
                        let loaded = 0, applied = false;

                        const override = () => {
                            if (applied) return;
                            applied = true;
                            const orig = window.doGTranslate;
                            window.doGTranslate = function(lang_pair) {
                                function unsplitText(element, preserveStroke = false) {
                                    // Store original text only once
                                    if (!element.hasAttribute("data-original-text")) {
                                        element.setAttribute("data-original-text", element.textContent);
                                    }
                                    // If translating back to original language (e.g., "en|en"), restore
                                    if (lang_pair.split("|")[1] === lang_pair.split("|")[0]) {
                                        element.innerHTML = element.getAttribute("data-original-text");
                                        return;
                                    }

                                    let stroke = element.getElementsByClassName("uicore-svg-wrapper")[0];

                                    // Clone stroke
                                    if (preserveStroke) {
                                        if (stroke) {
                                            stroke = stroke.cloneNode(true);
                                        }
                                    }

                                    const text = Array.from(element.childNodes)
                                        .filter(node => node.nodeType === Node.TEXT_NODE || node.nodeType === Node.ELEMENT_NODE)
                                        .map(node => node.textContent)
                                        .join("");

                                    element.innerHTML = "<span class=\"ui-e-headline-highlighted gtranslate-space\">" + text + "</span>";

                                    if(preserveStroke && stroke) {
                                        element.appendChild(stroke);
                                    }

                                }

                                let children;
                                const splitElements = document.querySelectorAll(".ui-splitby-chars");

                                splitElements.forEach(el => {
                                    // Highlighted Text widget
                                    if (el.classList.contains("elementor-widget-highlighted-text")) {
                                        children = el.querySelectorAll(".ui-e-headline-text");
                                        children.forEach(child => unsplitText(child, true))
                                    // Heading widget
                                    } else if (el.classList.contains("elementor-widget-heading")) {
                                        children = el.querySelectorAll(".elementor-heading-title");
                                        children.forEach(child => unsplitText(child));

                                    // Refine version for text editor widget that can pretty much contain all sorts of markup
                                    } else {
                                        unsplitText(el);
                                    }
                                });

                                orig(lang_pair);
                            };
                        };

                        const poll = () => {
                            if (typeof window.doGTranslate === "function") {
                                if (!applied || window.doGTranslate.toString().indexOf("Custom doGTranslate applied") === -1) {
                                    applied = false;
                                    override();
                                }
                            }
                            setTimeout(poll, 100);
                        };

                        const onAllLoaded = () => poll();

                        scripts.forEach(s => s.addEventListener("load", () => {
                            if (++loaded === scripts.length) onAllLoaded();
                        }));
                    </script>';
            }
        }

        return $tag;
    }

    /**
     * Enqueue animation style
     *
     */
    public function remove_elementor_animation_style()
    {
        wp_dequeue_style('elementor-animations');
    }

    /**
     * Enqueue scroll
     *
     */
    public function enqueue_scripts()
    {
        //scroll
        if (Settings::get_option('uianim_scroll')  == 'true') {
            wp_enqueue_script('uianim-scroll', UICORE_ANIMATE_ASSETS . '/js/scroll.js',  UICORE_ANIMATE_VERSION, true);
        }
        wp_enqueue_script('uianim-entrance-animation', UICORE_ANIMATE_ASSETS . '/js/entrance-animation.js',  UICORE_ANIMATE_VERSION, true);
    }



    public function add_css_to_framework($files, $settings)
    {
        if ($settings['performance_animations'] === 'true') {
            $style = $settings['uianim_style'];

            // TODO: Apparently when activating framework with Animate enabled, the `uianim_style` setting might be an array.
            // After saving becomes a string. Is a good idea to investigate and insure consistency in the data format.
            if (is_array($style)) {
                $style = (isset($style['value']) && $style['value']) ? $style['value'] : 'style1';
            } else {
                $style = (isset($style)) ? $style : 'style1';
            }

            $files[] = UICORE_ANIMATE_PATH . '/assets/css/' . $style . '.css';
        }

        return $files;
    }

    public function add_js_to_framework($files, $settings)
    {
        if (
            $settings['performance_animations'] === 'true'
            && $settings['animations'] === 'true'
            && $settings['uianim_scroll'] === 'true'
        ) {
            $files[] =  UICORE_ANIMATE_PATH . '/assets/js/scroll.js';
        }

        $files[] =  UICORE_ANIMATE_PATH . '/assets/js/entrance-animation.js';

        return $files;
    }

    public function add_scroll_timeline_polyfill()
    { ?>
        <style>
            .uicore-animate-scroll {
                animation-fill-mode: both;
                animation-timing-function: linear;
                animation-timeline: view(block);
            }

            .uicore-animate-hide {
                opacity: 0;
                visibility: hidden;
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const supportsAnimationTimeline = CSS.supports("animation-timeline", "scroll()");

                if (!supportsAnimationTimeline && document.querySelector('.uicore-animate-scroll')) {
                    const script = document.createElement('script');
                    script.src = "<?php echo UICORE_ANIMATE_ASSETS . '/js/scroll-timeline.js'; ?>";
                    script.async = true;
                    document.head.appendChild(script);
                }
            });
        </script>
<?php
    }
}
