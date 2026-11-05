import '@fontsource/marcellus/index.css';
import '@fontsource-variable/figtree/index.css';

import { initAgenda } from './modules/agenda';
import { initCarousels } from './modules/carousel';
import { initNewsletter } from './modules/newsletter';
import { initShare } from './modules/share';

initNewsletter();
initShare();
initCarousels();
initAgenda();
