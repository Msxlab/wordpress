<?php
defined('ABSPATH') || exit();

//INCLUDED IN CLASS JS
if($settings['menu_interaction'] === 'focus'){
  $js .= "
  document.body.classList.add('uicore-menu-focus');
  ";
}elseif($settings['menu_interaction'] === 'text flip'){
    $js .= "
    if(window.innerWidth >= ".$settings["mobile_breakpoint"]."){
        document.querySelectorAll('.uicore-menu li').forEach(function(li) {
            li.addEventListener('mouseenter', function(e) {
                var animClass = 'ui-anim-flip';
                if (!li.classList.contains(animClass)) {
                    var a = li.querySelector('a');
                    if (!a) return;
                    var btnContent = a.querySelector('.ui-menu-item-wrapper');
                    if (!btnContent) return;

                    // Clone and insert after
                    var clone = btnContent.cloneNode(true);
                    btnContent.parentNode.insertBefore(clone, btnContent.nextSibling);

                    // Wrap all .ui-menu-item-wrapper in a new div
                    var wrappers = a.querySelectorAll('.ui-menu-item-wrapper');
                    var wrapDiv = document.createElement('div');
                    wrapDiv.className = 'ui-flip-anim-wrapp';
                    wrappers.forEach(function(w) {
                        wrapDiv.appendChild(w);
                    });
                    a.appendChild(wrapDiv);

                    setTimeout(function() {
                        li.classList.add(animClass);
                    }, 10);
                }
            });
        });
    }
    ";
}elseif($settings['menu_interaction'] === 'magnet button'){
    $js .= '
    if(window.innerWidth >= '.$settings["mobile_breakpoint"].'){
        const nav = document.querySelector("ul.uicore-menu");
        const menu = document.querySelector("#wrapper-navbar .uicore-header-wrapper");
        const ctaBtn = document.querySelector(".uicore-cta-wrapper a");
        let customTop = ctaBtn ? ctaBtn.getBoundingClientRect().top : 0;
        let customHeight = ctaBtn ? ctaBtn.offsetHeight : 0;
        let isPilCompact = false;
    ';
    if (in_array($settings['header_pill'], ['menu', 'logo-menu', 'true'])) {
        $js.='
            const heightFallback = (nav.offsetHeight - 8) + "px";
            const topFallback = (nav.getBoundingClientRect().top + window.scrollY - window.scrollY + 4) + "px";
            const isPill = true;
            ';
    }else if($settings['header_pill'] === 'compact'){
        $js.='
            const heightFallback = "2.4rem";
            const topFallback = "calc(calc(var(--uicore-header--menu-typo-h) / 2) - 1.2rem)";
            const isPill = false;
            customTop = customTop - (customHeight / 2) + 2;
            isPilCompact = true;
            ';
    }else{
        $js.='
            const heightFallback = "2.4rem";
            const topFallback = "calc(calc(var(--uicore-header--menu-typo-h) / 2) - 1.2rem)";
            const isPill = false;
            ';
    }

    if ($settings['header_layout'] === 'center_creative') {
        $bpadding = intval($settings['header_2_padding'] ?? 15);
        $js.='
            let creativeWrp = document.querySelector(".uicore-menu-container > ul > li span");
            console.log(creativeWrp);
            const itemSpacing = creativeWrp ? creativeWrp.offsetHeight * 1.33 : 0;
            customHeight = creativeWrp ? creativeWrp.offsetHeight * 2 : 0;
            customTop = creativeWrp ? creativeWrp.getBoundingClientRect().top - 10 : 0;
            ';
    }

    // - menu.getBoundingClientRect().top)

    $js .= '
        const fakeBtnHeight = ctaBtn ? customHeight + "px" : heightFallback;
        const fakeBtnTop = ctaBtn ? customTop + "px" : topFallback;
        const fakeBtnRadius = ctaBtn ? window.getComputedStyle(ctaBtn).borderRadius : "var(--ui-radius)";

        const updateActiveItemStyles = (anchor) => {
            const isLastChild = anchor.parentElement === anchor.parentElement.parentElement.lastElementChild;
            const anchorBounds = anchor.getBoundingClientRect();
            const navBounds = isPill ? 0 : menu.getBoundingClientRect().left;
            const relativeLeft = anchorBounds.left - navBounds;
            let width = anchorBounds.width +"px";
            // TODO: check what context exactly we need this left padding, because in all tests so far it does not help at all
            if(!isPill && !isPilCompact && isLastChild){
                width = "calc("+anchorBounds.width+"px + "+window.getComputedStyle(anchor).paddingLeft+")";
            }

            nav.style.setProperty("--item-active-x", `${relativeLeft}px`);
            nav.style.setProperty("--item-active-width", width);
        };

        nav.querySelectorAll("li > a").forEach(function(a) {
            a.addEventListener("pointerenter", function() {
                updateActiveItemStyles(this);
            });
        });

        const deactivate = async () => {
            const transitions = nav.getAnimations();
            if (transitions.length) {
                const fade = transitions.find(t => t.effect.target === nav.firstElementChild && t.transitionProperty === "opacity");
                await Promise.allSettled([fade.finished]);
                nav.style.setProperty("--item-active-x", "");
                nav.style.setProperty("--item-active-width", "");
            }
        };

        nav.addEventListener("pointerleave", deactivate);
        nav.addEventListener("blur", deactivate);
        nav.style.setProperty("--item-active-height", `${fakeBtnHeight}`);
        nav.style.setProperty("--item-active-y", `${fakeBtnTop}`);
        nav.style.setProperty("--item-active-radius", `${fakeBtnRadius}`);
    }
    ';
    //if(window.innerWidth >= '.$settings["mobile_breakpoint"].'){ }
}
if($settings['submenu_trigger'] === 'click' && strpos($settings['header_layout'], 'ham') == false){
  $js .= "
    if (window.innerWidth >= ".$settings["mobile_breakpoint"].") {
        document.querySelectorAll('.uicore-nav .sub-menu').forEach(function(subMenu) {
            subMenu.style.display = 'none';
        });

        document.querySelectorAll('.uicore-nav .menu-item-has-children').forEach(function(menuItem) {
            menuItem.addEventListener('click', function(e) {
                const target = e.target;
                const parentLi = target.closest('li');
               if (
                    (target.tagName === 'LI' ||
                        (target.tagName === 'A' && target.parentElement && target.parentElement.tagName === 'LI') ||
                        target.classList.contains('ui-menu-item-wrapper')
                    ) &&
                    (parentLi && parentLi.classList.contains('menu-item-has-children') &&
                        (!target.classList.length || (target.classList.length === 1 && target.classList.contains('ui-menu-item-wrapper')))
                    ) && (
                        !target.querySelector('.uicore-megamenu')
                    )
                ) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                const sub = menuItem.querySelector(':scope > .sub-menu');

                // Hide other open sub-menus
                const siblings = Array.from(menuItem.parentElement.children).filter(
                    el => el !== menuItem
                );
                siblings.forEach(function(sibling) {
                    const siblingSub = sibling.querySelector(':scope > .sub-menu');
                    if (siblingSub) siblingSub.style.display = 'none';
                    sibling.querySelectorAll('.sub-menu').forEach(function(subMenu) {
                        subMenu.style.display = 'none';
                    });
                });

                if (sub && (sub.style.display === '' || sub.style.display === 'none')) {
                    sub.style.display = 'flex';
                    sub.style.opacity = 1;
                    sub.style.transform = 'none';
                } else if (sub) {
                    sub.style.display = 'none';
                }
            });
        });

        document.addEventListener('click', function(e) {
            const clickedElement = e.target;
            if (!clickedElement.closest('.sub-menu')) {
                document.querySelectorAll('.sub-menu').forEach(function(subMenu) {
                    subMenu.style.display = 'none';
                });
            }
        });
    }
  ";
}
if($settings['animations_submenu'] === 'scale bg' && in_array($settings['header_layout'], ['center', 'classic_center', 'center_creative'])){
  $classic_center = $settings['header_layout'] === 'center_creative' ? 'true' : 'false';
  $pill_adjustment = in_array($settings['header_pill'], ['menu', 'logo-menu']) ? 'true' : 'false';
  $js .= "
    document.addEventListener('DOMContentLoaded', function() {
        let timeout;

        const trigger = '" .  $settings['submenu_trigger']. "';
        const triggerIn = trigger === 'hover' ? 'mouseenter' : trigger;

        document.querySelectorAll('.uicore-menu > li.menu-item-has-children').forEach(function(li) {

            if (trigger === 'click') {
                li.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (li.classList.contains('ui-submenu-opened')) {
                        closeSubmenu(timeout, li);
                    } else {
                        document.querySelectorAll('.uicore-menu > li.menu-item-has-children.ui-submenu-opened').forEach(function(openLi) {
                            if (openLi !== li) {
                                openLi.classList.remove('ui-submenu-opened');
                            }
                        });

                        const subMenu = li.querySelector('.sub-menu');
                        let maxHeight = 0;
                        if (subMenu) {
                            const deepestSubMenu = findDeepestSubMenu(subMenu);
                            if (deepestSubMenu) {
                                const subMenuRect = deepestSubMenu.getBoundingClientRect();
                                const liRect = li.getBoundingClientRect();
                                maxHeight = subMenuRect.bottom - liRect.top;
                            }
                        }

                        clearTimeout(timeout);

                        document.querySelectorAll('.uicore-header-wrapper').forEach(function(header) {
                            header.style.setProperty('--ui-bg-height', Math.max(maxHeight, 100) + 'px');
                        });

                        const menu = document.querySelector('.uicore-transparent');
                        if (menu) {
                            menu.classList.add('uicore-transparent-color');
                        }

                        li.classList.add('ui-submenu-opened');
                    }
                });

                // Close submenu and bg animation only when clicking outside any menu item with submenu
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.uicore-menu > li.menu-item-has-children')) {
                        document.querySelectorAll('.uicore-menu > li.menu-item-has-children.ui-submenu-opened').forEach(function(openLi) {
                            closeSubmenu(timeout, openLi, true);
                        });
                    }
                });
            } else {
                li.addEventListener(triggerIn, function() {
                    document.querySelectorAll('.uicore-menu > li.menu-item-has-children.ui-submenu-opened').forEach(function(openLi) {
                        if (openLi !== li) {
                            openLi.classList.remove('ui-submenu-opened');
                            document.querySelectorAll('.uicore-header-wrapper').forEach(function(header) {
                                header.style.setProperty('--ui-bg-height', '100%');
                            });
                            document.querySelectorAll('.uicore-transparent-color').forEach(function(el) {
                                el.classList.remove('uicore-transparent-color');
                            });
                        }
                    });

                    const subMenu = li.querySelector('.sub-menu');
                    let maxHeight = 0;

                if (subMenu) {
                    // Find the deepest nested sub-menu to calculate total height
                    const deepestSubMenu = findDeepestSubMenu(subMenu);
                    if (deepestSubMenu) {
                        const subMenuRect = deepestSubMenu.getBoundingClientRect();
                        const liRect = li.getBoundingClientRect();
                        if ($classic_center) {
                            const mainRect = document.querySelector('.uicore-header-wrapper').getBoundingClientRect();
                            maxHeight = subMenuRect.bottom + " . ($settings['header_2_padding'] ?? 20)  ." - mainRect.top;
                        } else {
                            maxHeight = subMenuRect.bottom - liRect.top;
                        }
                    }
                }

                    clearTimeout(timeout);

                    if($pill_adjustment){
                        console.log('Is pill');
                        document.querySelectorAll('.uicore-header-wrapper .uicore-nav-menu ul.uicore-menu').forEach(function(header) {
                            header.style.setProperty('height', Math.max(maxHeight, 100) + 'px');
                        });
                    } else {
                        console.log('Not pill');
                        document.querySelectorAll('.uicore-header-wrapper').forEach(function(header) {
                            header.style.setProperty('--ui-bg-height', Math.max(maxHeight, 100) + 'px');
                        });
                    }

                    const menu = document.querySelector('.uicore-transparent');
                    if (menu) {
                        menu.classList.add('uicore-transparent-color');
                    }

                    li.classList.add('ui-submenu-opened');
                });

                li.addEventListener('mouseleave', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(function() {
                        li.classList.remove('ui-submenu-opened');
                        if($pill_adjustment){
                            document.querySelectorAll('.uicore-header-wrapper .uicore-nav-menu ul.uicore-menu').forEach(function(header) {
                                header.style.setProperty('height', 'auto');
                            });
                        } else {
                            document.querySelectorAll('.uicore-header-wrapper').forEach(function(header) {
                                header.style.setProperty('--ui-bg-height', '100%');
                            });
                        }
                        setTimeout(() => {
                            document.querySelectorAll('.uicore-transparent-color').forEach(function(el) {
                                el.classList.remove('uicore-transparent-color');
                            });
                        }, 70);
                    }, 65);
                });
            }
        });

        function closeSubmenu(timeout, li, closeBg = true) {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                if (closeBg) {
                    document.querySelectorAll('.uicore-header-wrapper').forEach(function(header) {
                        header.style.setProperty('--ui-bg-height', '100%');
                    });
                    setTimeout(() => {
                        document.querySelectorAll('.uicore-transparent-color').forEach(function(el) {
                            el.classList.remove('uicore-transparent-color');
                        });
                    }, 70);
                }
            }, 65);
            li.classList.remove('ui-submenu-opened');
        }

        function findDeepestSubMenu(element) {
            const subMenus = element.querySelectorAll('.sub-menu');
            let deepest = element;
            let maxDepth = 0;

            subMenus.forEach(subMenu => {
                const depth = getSubMenuDepth(subMenu);
                if (depth > maxDepth) {
                    maxDepth = depth;
                    deepest = subMenu;
                }
            });

            return deepest;
        }

        function getSubMenuDepth(element) {
            let depth = 0;
            let current = element;

            while (current) {
                const parentSubMenu = current.closest('.sub-menu');
                if (parentSubMenu && parentSubMenu !== current) {
                    depth++;
                    current = parentSubMenu;
                } else {
                    break;
                }
            }

            return depth;
        }
    });
  ";
}
if($settings['animations_submenu'] === 'website blur'){
  $js .= "
    const content = document.getElementById('content');
    if (content) {
        content.style.transition = 'all 0.3s cubic-bezier(.33,1,.68,1)';
        content.style.willChange = 'filter transform';
    }

    let removeBlurTimeout;
    document.querySelectorAll('.uicore-menu li.menu-item-has-children').forEach(function(li) {
        li.addEventListener('mouseenter', function() {
            clearTimeout(removeBlurTimeout);
            document.body.classList.add('uicore-blur-on');
        });
        li.addEventListener('mouseleave', function() {
            removeBlurTimeout = setTimeout(() => {
                document.body.classList.remove('uicore-blur-on');
            }, 70);
        });
    });
  ";
}

if($settings['mmenu_animation'] === 'expand'){
  $js .= "
    window.uicoreBeforeMobileMenuShow = function() {
        const wrapper = document.querySelector('.uicore-mobile-menu-wrapper');
        const nav = wrapper ? wrapper.querySelector('nav') : null;
        const activeUls = wrapper ? wrapper.querySelectorAll('ul.uicore-active') : [];
        const lastActiveUl = activeUls.length ? activeUls[activeUls.length - 1] : null;
        const extra = wrapper ? wrapper.querySelector('.uicore-extra') : null;

        const navHeight = nav ? nav.offsetHeight : 0;
        const lastUlHeight = lastActiveUl ? lastActiveUl.offsetHeight : 0;
        const extraHeight = extra ? extra.offsetHeight : 0;

        const height = navHeight + lastUlHeight + extraHeight + 30;
        const heightCalc = 'calc(' + height + 'px + 2em)';
        if (wrapper) {
            wrapper.style.maxHeight = heightCalc;
        }
    };

    window.uicoreBeforeMobileMenuHide = function() {
        const wrapper = document.querySelector('.uicore-mobile-menu-wrapper');
        if (wrapper) {
            wrapper.style.maxHeight = '0';
        }
    };
  ";
}

if($settings['header_layout'] !== 'left'){

  $center_creative = $settings['header_layout'] === 'center_creative' ? 'true' : 'false';
  $js .= "
    document.addEventListener('DOMContentLoaded', function() {

        const mainHeader = document.querySelector('.uicore-header-wrapper');
        const tbHeader = document.querySelector('#uicore-tb-header');

        if (!tbHeader) return;

        let isDefaultKept = tbHeader.classList.contains('uicore-tb-header-with-default');
        let isSwitched = tbHeader.classList.contains('uicore-tb-switched');

        if (!isDefaultKept) return;

        let headerHeight = tbHeader.offsetHeight; // get tb header height as default

        // Discover which header are sticky
        let stickyHeader = document.querySelector('.uicore-navbar.uicore-sticky');
        let isTbHeaderSticky = tbHeader.style.position === 'sticky';


        if (isSwitched) {
            //
            headerHeight = mainHeader.offsetHeight; // if tb header is below than we need default header height data
            let tbHeaderStyles = {};
            if (isTbHeaderSticky) {
                tbHeaderStyles = { 'top': headerHeight + 'px'};
                window.addEventListener('scroll', function() {
                    let scrollY = window.scrollY || window.pageYOffset;
                    let topValue = Math.max(headerHeight - scrollY, 0);

                    // if sibling tb header is not sticky we decrease the top value adjustment progressively
                    if (!stickyHeader) {
                        tbHeader.style.setProperty('top', topValue + 'px', 'important');
                    } else if (" . $center_creative . ") { //
                         window.addEventListener('scroll', function() {
                            if ((window.scrollY || window.pageYOffset) === 0) {
                                tbHeader.style.setProperty('top', topValue + 'px', 'important');
                            } else if (stickyHeader.classList.contains('uicore-scrolled')) {
                                let mainHeaderTop = parseInt(window.getComputedStyle(mainHeader).top) || 0;
                                let newTopValue = headerHeight + mainHeaderTop;
                                tbHeader.style.setProperty('top', newTopValue + 'px', 'important');
                            }
                        });
                    }
                });
            } else {
                tbHeaderStyles = {
                    top: headerHeight + 'px',
                    position: 'absolute',
                    width: '100%',
                    zIndex: '10'
                }
            }

            Object.assign(tbHeader.style, tbHeaderStyles);

        } else {

            if (stickyHeader) {
                stickyHeader.style.setProperty('top', headerHeight + 'px', 'important');
                window.addEventListener('scroll', function() {
                    let scrollY = window.scrollY || window.pageYOffset;

                    // if sibling tb header is not sticky we decrease the top value adjustment progressively
                    if (!isTbHeaderSticky) {
                        let topValue = Math.max(headerHeight - scrollY, 0);
                        stickyHeader.style.setProperty('top', topValue + 'px', 'important');
                    }
                });
            }
        }

    });
  ";

}