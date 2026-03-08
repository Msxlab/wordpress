<?php
defined('ABSPATH') || exit();
//INCLUDED IN CLASS JS

$js .= "window.addEventListener('DOMContentLoaded', () => {";
if ($settings['button_interaction'] === 'attract') {
  $js .= "
  document.querySelectorAll('".$css_selectors."').forEach(button => {
    button.addEventListener('mousemove', function(e) {
      const pos = this.getBoundingClientRect();
      const mx = e.clientX - pos.left - pos.width / 2;
      const my = e.clientY - pos.top - pos.height / 2;

      this.style.transform = 'translate(' + mx * 0.15 + 'px, ' + my * 0.3 + 'px)';
      this.style.transform += 'rotate3d(' + mx * -0.1 + ', ' + my * -0.3 + ', 0, 12deg)';
      if (this.children[0]) {
        this.children[0].style.transition = 'all .2s linear';
        this.children[0].style.transform = 'translate(' + mx * 0.07 + 'px, ' + my * 0.14 + 'px)';
      }
    });

    button.addEventListener('mouseleave', function() {
      this.style.transform = 'translate3d(0px, 0px, 0px)';
      this.style.transform += 'rotate3d(0, 0, 0, 0deg)';
      if (this.children[0]) {
        this.children[0].style.transform = 'translate3d(0px, 0px, 0px)';
      }
    });
  });
  ";
} elseif (substr($settings['button_interaction'], 0, 5) === "text ") {
  $js .= "
    document.querySelectorAll('".$css_selectors."').forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const animClass = 'elementor-animation-" . str_replace('text ', '', $settings['button_interaction']) . "';
            if (!this.classList.contains(animClass)) {

                // Handle spans without class attribute
                const spansWithoutClass = Array.from(this.children).filter(child =>
                    child.tagName === 'SPAN' && !child.getAttribute('class')
                );

                if (spansWithoutClass.length > 0 && !spansWithoutClass.some(span =>
                    span.querySelector('.elementor-button-text')
                )) {
                    spansWithoutClass.forEach(span => span.classList.add('elementor-button-text'));
                }

                // Wrap inner content if no children
                if (this.children.length === 0) {
                    const wrapper = document.createElement('span');
                    wrapper.className = 'elementor-button-text';
                    wrapper.innerHTML = this.innerHTML;
                    this.innerHTML = '';
                    this.appendChild(wrapper);
                }

                // Find button content and add class
                const btnContentElements = this.querySelectorAll('.elementor-button-text, .bdt-newsletter-btn-text, .bdt-scroll-button-text');
                btnContentElements.forEach(element => {
                    element.classList.add('elementor-button-text');
                });

                // Clone and insert after each element
                btnContentElements.forEach(element => {
                    const clone = element.cloneNode(true);
                    element.parentNode.insertBefore(clone, element.nextSibling);
                });

                // Wrap all elementor-button-text elements
                const btnTextElements = this.querySelectorAll('.elementor-button-text');
                if (btnTextElements.length > 0) {
                    const wrapper = document.createElement('span');
                    wrapper.className = 'ui-btn-anim-wrapp';

                    // Insert wrapper before first element
                    btnTextElements[0].parentNode.insertBefore(wrapper, btnTextElements[0]);

                    // Move all elements into the wrapper
                    btnTextElements.forEach(element => {
                        wrapper.appendChild(element);
                    });
                }

                // Add animation class after delay
                setTimeout(() => {
                    this.classList.add(animClass);
                }, 30);
            }
        });
    });
  ";
} else {
  $js .= "
  document.querySelectorAll('".$css_selectors."').forEach(button => {
    button.addEventListener('mouseenter', function() {
      this.classList.add('elementor-animation-".$settings['button_interaction']."');
    });
  });
  ";
}
$js .= "}, false);";