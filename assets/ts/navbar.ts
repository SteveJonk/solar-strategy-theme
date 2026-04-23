export const navbar = () => {
  const openButton = document.getElementById('js__open-nav');
  const closeButton = document.getElementById('js__close-nav');
  const navBar = document.getElementById('js__nav');
  const header = document.querySelector('.header');

  openButton?.addEventListener('click', (event) => {
    event.preventDefault();
    navBar.classList.add('open');
    header?.classList.add('hidden');
    document.body.style.overflow = 'hidden';
  });

  closeButton?.addEventListener('click', (event) => {
    event.preventDefault();
    navBar.classList.remove('open');
    header?.classList.remove('hidden');
    document.body.style.overflow = 'auto';
  });

  /**
   * Toggle the items of the navbar dropdown
   *
   * @returns {void}
   */

  const navBarDropdownLinks = document.querySelectorAll('.menu-item-has-children > a');
  const deeperNavBarDropdownLinks = document.querySelectorAll(
    '.menu-item-has-children .sub-menu .menu-item-has-children > a',
  );

  const SHOW_CLASSNAME = 'show-sub-menu';

  navBarDropdownLinks.forEach((navBarDropdownLink) => {
    navBarDropdownLink?.addEventListener('click', (event) => {
      event.preventDefault();

      if (!navBarDropdownLink.parentElement.classList.contains(SHOW_CLASSNAME)) {
        navBarDropdownLink.parentElement.classList.add(SHOW_CLASSNAME);
      } else {
        deeperNavBarDropdownLinks.forEach((deeperLink) => {
          deeperLink.parentElement.classList.remove(SHOW_CLASSNAME);
        });
        navBarDropdownLink.parentElement.classList.remove(SHOW_CLASSNAME);
      }
    });
  });

  /**
   * Add class to navbar when scrolled
   *
   * @returns {void}
   */
  let ticking = false;

  window.addEventListener('scroll', () => {
    if (!ticking) {
      requestAnimationFrame(() => {
        if (window.scrollY > 100) {
          header.classList.add('scrolled');
        } else {
          header.classList.remove('scrolled');
        }
        ticking = false;
      });
      ticking = true;
    }
  });
};
