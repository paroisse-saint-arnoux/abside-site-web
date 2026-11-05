// Admin : champs image (App\Filament\Forms\ImageUpload).
// - glisser-déposer sur tout le champ, même quand une image est déjà présente (elle est alors remplacée) ;
// - bouton « Remplacer » sur l'image présente ;
// - Cropper.js pour l'outil « Recadrer / pivoter » (resources/views/filament/image-cropper.blade.php).
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

window.Cropper = Cropper;

// Composant Alpine du champ de Filament (il porte l'instance FilePond : component.pond).
const componentOf = (field) => {
    const root = field.querySelector('.filepond--root');
    if (!root || !window.Alpine) return null;
    try {
        const data = window.Alpine.$data(root);
        return data?.pond ? data : null;
    } catch {
        return null;
    }
};

// Remplacement : FilePond retire l'ancienne image puis envoie la nouvelle. Le retrait vide l'état du champ, et
// Filament recharge alors la liste des fichiers… en effaçant la nouvelle image. On suspend cette mise à jour
// le temps de l'envoi (Filament la rétablit lui-même à la fin de l'envoi).
const replaceWith = (component, file) => {
    component.shouldUpdateState = false;
    component.pond.addFile(file).catch(() => {}).finally(() => {
        setTimeout(() => { if (!component.activeUploads) component.shouldUpdateState = true; }, 15000);
    });
};

const enhance = (field) => {
    if (field.dataset.imageEnhanced) return;
    const component = componentOf(field);
    if (!component) return;
    const pond = component.pond;
    field.dataset.imageEnhanced = '1';

    // Bouton « Remplacer », affiché quand une image est présente.
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'ab-replace-btn';
    button.textContent = 'Remplacer';
    button.addEventListener('click', () => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/jpeg,image/png,image/webp,image/gif';
        input.addEventListener('change', () => input.files[0] && replaceWith(component, input.files[0]));
        input.click();
    });
    field.appendChild(button);
    const sync = () => field.classList.toggle('ab-has-image', pond.getFiles().length > 0);
    ['addfile', 'removefile', 'init'].forEach((e) => pond.on(e, sync));
    sync();

    // Glisser-déposer sur tout le champ (FilePond refuse le dépôt quand le champ est déjà plein).
    let depth = 0;
    field.addEventListener('dragenter', (e) => {
        if (!e.dataTransfer?.types.includes('Files')) return;
        depth++;
        field.classList.add('ab-drop');
    }, true);
    field.addEventListener('dragleave', () => {
        depth = Math.max(0, depth - 1);
        if (!depth) field.classList.remove('ab-drop');
    }, true);
    field.addEventListener('dragover', (e) => {
        if (!e.dataTransfer?.types.includes('Files')) return;
        e.preventDefault();
        e.stopPropagation();
    }, true);
    field.addEventListener('drop', (e) => {
        const file = [...(e.dataTransfer?.files ?? [])].find((f) => f.type.startsWith('image/'));
        depth = 0;
        field.classList.remove('ab-drop');
        if (!file) return;
        e.preventDefault();
        e.stopPropagation();
        replaceWith(component, file);
    }, true);
};

const scan = () => document.querySelectorAll('[data-image-upload]').forEach(enhance);
new MutationObserver(() => requestAnimationFrame(scan)).observe(document.documentElement, { childList: true, subtree: true });
document.addEventListener('livewire:navigated', scan);
scan();
