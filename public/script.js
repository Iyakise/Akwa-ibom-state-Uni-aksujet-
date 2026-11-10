(() => {
  const menuToggle = document.querySelector('.menu-toggle');
  const navigation = document.querySelector('.primary-navigation');
  const searchInput = document.querySelector('#library-search');
  const filterButtons = [...document.querySelectorAll('[data-filter]')];
  const cards = [...document.querySelectorAll('.searchable')];
  const noResults = document.querySelector('#no-results');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let activeFilter = 'all';

  const year = document.querySelector('#current-year');
  if (year) year.textContent = new Date().getFullYear();

  document.querySelectorAll('[data-journal-download]').forEach(link => {
    const card = link.closest('[data-kind="journal"]');
    const pdfPath = (card?.dataset.journalPdf || '').trim();
    if (!pdfPath) return;
    link.href = pdfPath;
    link.setAttribute('download', '');
    link.hidden = false;
  });

  if (menuToggle && navigation) {
    const closeMenu = () => {
      menuToggle.setAttribute('aria-expanded', 'false');
      menuToggle.setAttribute('aria-label', 'Open navigation');
      navigation.classList.remove('is-open');
    };
    menuToggle.addEventListener('click', () => {
      const open = menuToggle.getAttribute('aria-expanded') !== 'true';
      menuToggle.setAttribute('aria-expanded', String(open));
      menuToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
      navigation.classList.toggle('is-open', open);
    });
    navigation.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
    document.addEventListener('click', event => {
      if (!navigation.contains(event.target) && !menuToggle.contains(event.target)) closeMenu();
    });
    window.addEventListener('resize', () => {
      if (window.innerWidth > 720) closeMenu();
    });
  }

  function applyFilters() {
    const query = (searchInput?.value || '').trim().toLowerCase();
    let visibleCount = 0;
    cards.forEach(card => {
      const kind = card.dataset.kind || '';
      const text = `${card.dataset.search || ''} ${card.textContent || ''}`.toLowerCase();
      const kindMatches = activeFilter === 'all' ||
        (activeFilter === 'journals' && kind === 'journal') ||
        (activeFilter === 'articles' && kind === 'article');
      const visible = kindMatches && (!query || text.includes(query));
      card.hidden = !visible;
      if (visible) visibleCount += 1;
    });
    if (noResults) noResults.hidden = visibleCount > 0;
  }

  if (searchInput) searchInput.addEventListener('input', applyFilters);
  filterButtons.forEach(button => button.addEventListener('click', () => {
    activeFilter = button.dataset.filter || 'all';
    filterButtons.forEach(item => {
      const selected = item === button;
      item.classList.toggle('is-active', selected);
      item.setAttribute('aria-pressed', String(selected));
    });
    applyFilters();
  }));

  document.addEventListener('keydown', event => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      searchInput?.focus();
      document.querySelector('#library')?.scrollIntoView({
        behavior: reducedMotion ? 'auto' : 'smooth'
      });
    }
  });

  const revealItems = document.querySelectorAll('.reveal');
  if (reducedMotion || !('IntersectionObserver' in window)) {
    revealItems.forEach(item => item.classList.add('is-visible'));
  } else {
    const revealObserver = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        revealObserver.unobserve(entry.target);
      });
    }, { threshold: 0.12 });
    revealItems.forEach(item => revealObserver.observe(item));
  }

  const archiveSource = document.querySelector('#source_path');
  const archiveTitle = document.querySelector('#archive-title');
  let archiveTitleEdited = false;
  archiveTitle?.addEventListener('input', () => { archiveTitleEdited = true; });
  archiveSource?.addEventListener('change', () => {
    const suggested = archiveSource.selectedOptions[0]?.dataset.suggestedTitle;
    if (suggested && archiveTitle && !archiveTitleEdited) archiveTitle.value = suggested;
  });

  const scene = document.querySelector('[data-folio-scene]');
  const folio = document.querySelector('[data-folio]');
  if (scene && folio && !reducedMotion && window.matchMedia('(pointer: fine)').matches) {
    scene.addEventListener('pointermove', event => {
      const rect = scene.getBoundingClientRect();
      const x = (event.clientX - rect.left) / rect.width - 0.5;
      const y = (event.clientY - rect.top) / rect.height - 0.5;
      folio.style.transform = `rotateY(${-22 + x * 11}deg) rotateX(${6 - y * 8}deg) rotateZ(${-5 + x * 2}deg)`;
    });
    scene.addEventListener('pointerleave', () => { folio.style.transform = ''; });
  }
})();


  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
      const message = form.getAttribute('data-confirm') || 'Continue with this action?';
      if (!window.confirm(message)) event.preventDefault();
    });
  });
