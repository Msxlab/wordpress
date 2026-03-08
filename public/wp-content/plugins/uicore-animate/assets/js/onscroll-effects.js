window.addEventListener('DOMContentLoaded', () => {
  // Constants for Configuration
  const DEFAULT_INTENSITY = 1;
  const DEFAULT_OFFSET_PERCENTAGE = 10;
  const DEFAULT_OFFSET_END_PERCENTAGE = 10;

  // Debounce Utility Function
  const debounce = (func, wait = 1, immediate = true) => {
    let timeout;
    return function () {
      const context = this,
        args = arguments;
      const later = function () {
        timeout = null;
        if (!immediate) func.apply(context, args);
      };
      const callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
      if (callNow) func.apply(context, args);
    };
  };
  class onScrollEffects extends elementorModules.frontend.handlers.Base {
    // Bind Events
    bindEvents() {
      if (!['', 'simple-sticky', 'mask-reveal', undefined].includes(this.getElementSettings('uicore_onscroll_effect'))) {
        let $items = this.$element.hasClass('e-con-boxed') ? this.$element.find('> .e-con-inner > .e-con') : this.$element.find('> .e-con');
        $items = this.init($items);
        window.addEventListener('scroll', debounce(() => this.handleScroll($items)));
        this.handleScroll($items);
      }
      this.addCss();
    }
    onElementChange(prop) {
      if (prop === 'uicore_onscroll_effect') {
        this.addCss();
      }
    }
    addCss() {
      //first try to remove the style tag if it exists
      const id = this.$element.attr('data-id');
      if (document.getElementById(`uicore-onscroll-${id}`)) {
        document.getElementById(`uicore-onscroll-${id}`).remove();
      }
      if (document.getElementById(`uicore-onscroll-sticky-${id}`)) {
        document.getElementById(`uicore-onscroll-sticky-${id}`).remove();
      }
      var style = null;
      if (this.getElementSettings('uicore_onscroll_effect') === 'mask-reveal') {
        style = document.createElement('style');
        style.id = `uicore-onscroll-${id}`;
        const toValue = elementorFrontend.isEditMode() ? '#ffffff24' : 'transparent';
        style.innerHTML = `
                    .elementor-element-${id}{
                        min-height: var(--ui-e-onscroll-reveal-height,170vh);
                        display: block;
                        -webkit-mask-image: radial-gradient(circle at 50% 98%,white 37%,${toValue} 50%);
                        mask-image:radial-gradient(circle at 50% 98%,white 37%,${toValue} 50%)
                    }
                    .elementor-element-${id} > .e-con-inner{
                        position:sticky;
                        height:auto;
                        top:var(--ui-e-onscroll-offset,0px);
                    }
                `;
      } else if (!['', undefined].includes(this.getElementSettings('uicore_onscroll_effect'))) {
        style = document.createElement('style');
        style.id = `uicore-onscroll-sticky-${id}`;
        style.innerHTML = `
                .elementor-element-${id}.e-con-boxed > .e-con-inner > *,
                .elementor-element-${id}.e-con-full > * {
                    position:sticky;
                    top:calc(var(--ui-e-onscroll-offset, 0px) + calc(var(--ui-e-onscroll-items-offset, 0px) * var(--item-index, 1)));
                    margin-top:var(--ui-e-onscroll-items-offset,0px);
                    transition-timing-function: cubic-bezier(0.17, 1.1, 0.42, 1) !important;
                    transition: background var(--background-transition, .3s), border var(--border-transition, .3s), box-shadow var(--border-transition, .3s), transform var(--e-con-transform-transition-duration, 1s);
                }
                `;
      }
      if (style) {
        document.head.appendChild(style);
      }
    }

    // Handle Scroll Event
    handleScroll(elements) {
      const animationOptions = this.getAnimationOptions();
      const offsetPercentage = animationOptions.offsetPercentage || DEFAULT_OFFSET_PERCENTAGE;
      const offsetEndPercentage = animationOptions.offsetEndPercentage || DEFAULT_OFFSET_END_PERCENTAGE;
      const offsetNextElementPercentage = animationOptions.nextElementOffsetPercentage || DEFAULT_OFFSET_PERCENTAGE;
      const intensity = animationOptions.intensity || DEFAULT_INTENSITY;
      const windowHeight = window.innerHeight;
      elements.forEach((element, index) => {
        const progress = this.calculateScrollProgress(element, windowHeight, offsetPercentage, offsetEndPercentage);
        const isLastElement = index === elements.length - 1 || index === elements.length - 2;
        const nextElementProgress = this.calculateNextElementProgress(elements, index, windowHeight, offsetNextElementPercentage);
        this.applyTransformations(element, index, elements.length, isLastElement, progress, nextElementProgress, intensity, animationOptions, elements.length);
      });
    }

    // Calculate Scroll Progress
    calculateScrollProgress(element, windowHeight, offsetPercentage, offsetEndPercentage) {
      const rect = element.getBoundingClientRect();
      const elementHeight = rect.height;
      const triggerOffset = rect.top + document.documentElement.scrollTop;
      const offset = elementHeight * offsetPercentage / 100;
      const start = triggerOffset - windowHeight + offset;
      const offsetEnd = elementHeight * offsetEndPercentage / 100;
      const end = triggerOffset + elementHeight - offsetEnd;
      return Math.max(0, Math.min(1, (document.documentElement.scrollTop - start) / (end - start)));
    }

    // Calculate Next Element Progress
    calculateNextElementProgress(elements, index, windowHeight, offsetNextElementPercentage) {
      let nextElementProgress = 0;
      if (index < elements.length - 1) {
        const nextElement = elements[index + 1];
        const nextRect = nextElement.getBoundingClientRect();
        const nextElementHeight = nextRect.height;
        const nextTriggerOffset = nextRect.top + document.documentElement.scrollTop;
        const offset = nextElementHeight * offsetNextElementPercentage / 100;
        const nextStart = nextTriggerOffset - windowHeight + offset;
        const nextEnd = nextTriggerOffset + nextElementHeight;
        nextElementProgress = Math.max(0, Math.min(1, (document.documentElement.scrollTop - nextStart) / (nextEnd - nextStart)));
      }
      return nextElementProgress;
    }

    // Apply Transformations
    applyTransformations(element, index, elementsLength, isLastElement, progress, nextElementProgress, intensity, animationOptions, length) {
      const excludeFromLast = animationOptions.excludeFromLast || [];
      const useNextElementProgress = animationOptions.useNextElementProgress || [];
      Object.keys(animationOptions.end).forEach(property => {
        if (isLastElement && excludeFromLast.includes(property)) {
          return;
        }
        const startValue = animationOptions.start && animationOptions.start[property] ? animationOptions.start[property] : animationOptions.end[property];
        const endValue = animationOptions.end[property];
        let value;
        if (typeof startValue === 'object' && typeof endValue === 'object' && startValue.value == undefined && endValue.value == undefined) {
          const propValues = Object.keys(startValue).map(prop => {
            const {
              value,
              unit
            } = startValue[prop];
            const start = parseFloat(value);
            const end = parseFloat(endValue[prop].value);
            const interpolatedValue = (end - start) * (useNextElementProgress.includes(property) ? nextElementProgress : progress) * Math.pow(intensity, elementsLength - 1 - index) + start;
            return `${prop}(${interpolatedValue}${unit})`;
          });
          value = propValues.join(' ');
        } else {
          const start = parseFloat(startValue.value);
          const end = parseFloat(endValue.value);
          value = (end - start) * (useNextElementProgress.includes(property) && length > 1 ? nextElementProgress : progress) * Math.pow(intensity, elementsLength - 1 - index) + start;
          value = `${value}${startValue.unit}`;
        }
        element.style.setProperty(property, value);
      });
    }

    // Init props on items
    init(elements) {
      elements = Array.from(elements);

      // Set extra properties on items based on the effect
      const extraProperties = {};
      if (this.getElementSettings('uicore_onscroll_effect') === 'sticky-scale-alt') {
        extraProperties['transform-origin'] = 'center bottom';
      } else if (['sticky-mask', 'sticky-mask-grow'].includes(this.getElementSettings('uicore_onscroll_effect'))) {
        extraProperties['clip-path'] = 'inset(0 var(--ui-e-onscroll-path,0%) round var(--ui-e-onscroll-path-radius,0))';
      }
      elements.forEach((element, index) => {
        // Set custom property for the index of the element
        element.style.setProperty('--item-index', index);

        // Set extra properties on items
        Object.keys(extraProperties).forEach(property => {
          element.style[property] = extraProperties[property];
        });
      });
      // Add empty div after the last element to prevent layout shift
      if (elements.length > 1) {
        const lastElement = elements[elements.length - 1];
        const lastElementHeight = lastElement.getBoundingClientRect().height;
        const emptyDiv = document.createElement('div');
        emptyDiv.classList = lastElement.classList;

        //TODO: maybe split in half if is mobile
        emptyDiv.style.height = `${lastElementHeight}px`;
        emptyDiv.style.opacity = 0;
        lastElement.after(emptyDiv);

        //add the empty div to the elements array
        elements.push(emptyDiv);
      }
      return elements;
    }

    // Get Animation Options
    getAnimationOptions() {
      const animation = this.getElementSettings('uicore_onscroll_effect');
      switch (animation) {
        case 'sticky-scale':
          return {
            start: {
              filter: {
                brightness: {
                  value: '100',
                  unit: '%'
                },
                contrast: {
                  value: '100',
                  unit: '%'
                }
              },
              transform: {
                scale: {
                  value: '1',
                  unit: ''
                }
              }
            },
            end: {
              filter: {
                brightness: {
                  value: '93',
                  unit: '%'
                },
                contrast: {
                  value: '105',
                  unit: '%'
                }
              },
              transform: {
                scale: {
                  value: '0.9',
                  unit: ''
                }
              }
            },
            excludeFromLast: ['filter'],
            useNextElementProgress: ['transform', 'filter'],
            intensity: 1.3,
            //used to adjust the intensity of the effect based on the index of the element
            offsetPercentage: 70 // start the animation when 10% of the element is visible
          };
        case 'sticky-scale-small':
          return {
            start: {
              filter: {
                brightness: {
                  value: '100',
                  unit: '%'
                },
                contrast: {
                  value: '100',
                  unit: '%'
                }
              },
              transform: {
                scale: {
                  value: '1',
                  unit: ''
                }
              }
            },
            end: {
              filter: {
                brightness: {
                  value: '96',
                  unit: '%'
                },
                contrast: {
                  value: '103',
                  unit: '%'
                }
              },
              transform: {
                scale: {
                  value: '0.9',
                  unit: ''
                }
              }
            },
            excludeFromLast: ['filter'],
            useNextElementProgress: ['transform', 'filter'],
            intensity: 1.5,
            //used to adjust the intensity of the effect based on the index of the element
            offsetPercentage: 90,
            // start the animation when 10% of the element is visible
            nextElementOffsetPercentage: 90
          };
        case 'sticky-scale-alt':
          return {
            start: {
              filter: {
                brightness: {
                  value: '100',
                  unit: '%'
                },
                contrast: {
                  value: '100',
                  unit: '%'
                }
              },
              transform: {
                scale: {
                  value: '1',
                  unit: ''
                }
              }
            },
            end: {
              filter: {
                brightness: {
                  value: '97',
                  unit: '%'
                },
                contrast: {
                  value: '105',
                  unit: '%'
                }
              },
              transform: {
                scale: {
                  value: '0.95',
                  unit: ''
                }
              }
            },
            excludeFromLast: ['filter'],
            useNextElementProgress: ['transform', 'filter'],
            intensity: 1.4,
            //used to adjust the intensity of the effect based on the index of the element
            offsetPercentage: 70 // start the animation when 10% of the element is visible
          };
        case 'sticky-scale-blur':
          return {
            start: {
              filter: {
                brightness: {
                  value: '100',
                  unit: '%'
                },
                contrast: {
                  value: '100',
                  unit: '%'
                },
                blur: {
                  value: '0',
                  unit: 'px'
                }
              },
              transform: {
                scale: {
                  value: '1',
                  unit: ''
                }
              }
            },
            end: {
              filter: {
                brightness: {
                  value: '93',
                  unit: '%'
                },
                contrast: {
                  value: '105',
                  unit: '%'
                },
                blur: {
                  value: '3',
                  unit: 'px'
                }
              },
              transform: {
                scale: {
                  value: '0.9',
                  unit: ''
                }
              }
            },
            excludeFromLast: ['filter'],
            useNextElementProgress: ['transform', 'filter'],
            intensity: 1.2,
            //used to adjust the intensity of the effect based on the index of the element
            offsetPercentage: 80 // start the animation when 10% of the element is visible
          };
        case 'sticky-scale-blur-small':
          return {
            start: {
              filter: {
                brightness: {
                  value: '100',
                  unit: '%'
                },
                contrast: {
                  value: '100',
                  unit: '%'
                },
                blur: {
                  value: '0',
                  unit: 'px'
                }
              },
              transform: {
                scale: {
                  value: '1',
                  unit: ''
                }
              }
            },
            end: {
              filter: {
                brightness: {
                  value: '93',
                  unit: '%'
                },
                contrast: {
                  value: '105',
                  unit: '%'
                },
                blur: {
                  value: '2',
                  unit: 'px'
                }
              },
              transform: {
                scale: {
                  value: '0.9',
                  unit: ''
                }
              }
            },
            excludeFromLast: ['filter'],
            useNextElementProgress: ['transform', 'filter'],
            intensity: 1.3,
            //used to adjust the intensity of the effect based on the index of the element
            offsetPercentage: 80,
            // start the animation when 10% of the element is visible
            nextElementOffsetPercentage: 90
          };
        case 'sticky-parallax':
          return {
            start: {
              filter: {
                brightness: {
                  value: '100',
                  unit: '%'
                },
                contrast: {
                  value: '100',
                  unit: '%'
                }
              },
              transform: {
                translateY: {
                  value: '0',
                  unit: '%'
                }
              }
            },
            end: {
              filter: {
                brightness: {
                  value: '98',
                  unit: '%'
                },
                contrast: {
                  value: '102',
                  unit: '%'
                }
              },
              transform: {
                translateY: {
                  value: '-20',
                  unit: '%'
                }
              }
            },
            excludeFromLast: ['filter'],
            useNextElementProgress: ['transform', 'filter'],
            intensity: 1.3,
            //used to adjust the intensity of the effect based on the index of the element
            offsetPercentage: 70 // start the animation when 10% of the element is visible
          };
        case 'sticky-mask':
          //uses clip-path to create a mask effect
          return {
            start: {
              '--ui-e-onscroll-path': {
                value: '0',
                unit: '%'
              },
              '--ui-e-onscroll-path-radius': {
                value: '0',
                unit: 'px'
              }
            },
            end: {
              '--ui-e-onscroll-path': {
                value: '14',
                unit: '%'
              },
              '--ui-e-onscroll-path-radius': {
                value: '30',
                unit: 'px'
              }
            },
            useNextElementProgress: ['--ui-e-onscroll-path', '--ui-e-onscroll-path-radius'],
            intensity: 1.3,
            //used to adjust the intensity of the effect based on the index of the element
            offsetPercentage: 80 // start the animation when 10% of the element is visible
          };
        case 'sticky-mask-grow':
          //uses clip-path to create a mask effect
          return {
            start: {
              '--ui-e-onscroll-path': {
                value: '5',
                unit: '%'
              },
              '--ui-e-onscroll-path-radius': {
                value: '20',
                unit: 'px'
              }
            },
            end: {
              '--ui-e-onscroll-path': {
                value: '0',
                unit: '%'
              },
              '--ui-e-onscroll-path-radius': {
                value: '0',
                unit: 'px'
              }
            },
            useNextElementProgress: ['--ui-e-onscroll-path', '--ui-e-onscroll-path-radius'],
            intensity: 4,
            //used to adjust the intensity of the effect based on the index of the element
            offsetPercentage: 1,
            // start the animation when 10% of the element is visible
            offsetEndPercentage: 85
          };
        default:
          return {};
      }
    }
  }

  // Init Elementor Hooks
  jQuery(window).on('elementor/frontend/init', () => {
    const addHandler = $element => {
      elementorFrontend.elementsHandler.addHandler(onScrollEffects, {
        $element
      });
    };
    elementorFrontend.hooks.addAction('frontend/element_ready/container', addHandler);
  });
}, false);