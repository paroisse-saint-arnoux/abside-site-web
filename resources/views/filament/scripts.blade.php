@vite(['resources/js/admin/images.js'])
<style>
    /* Barre « Enregistrer / Annuler » fixée en bas (pages avec $formActionsAreSticky) : seulement quand le
       formulaire a des modifications non enregistrées. Sinon, elle reste à sa place, en bas du formulaire. */
    body:not(.ab-form-dirty) .fi-sticky .fi-ac {
        position: static; width: auto !important; margin: 0; padding: 0; background: none; box-shadow: none; border-radius: 0;
    }
    body.ab-form-dirty .fi-sticky .fi-ac { box-shadow: 0 -4px 24px rgba(21, 46, 71, .18), 0 0 0 2px #E9BE4A; }
</style>
<script>
    // Insère du Markdown à l'endroit du curseur de l'éditeur Markdown (EasyMDE / CodeMirror de Filament),
    // appelé par l'action « Insérer une image » de la bibliothèque (à venir).
    window.abInsertMarkdown = (text) => {
        const root = [...document.querySelectorAll('[x-data]')].find((el) => el._editor);
        if (! root) return;
        const codemirror = Alpine.raw(root._editor).codemirror;
        codemirror.focus();
        codemirror.replaceSelection(text);
    };

    // Formulaire modifié ? Même calcul que l'alerte « modifications non enregistrées » de Filament :
    // empreinte des données du formulaire comparée à celle des données enregistrées.
    (() => {
        let timer = null;
        const update = () => {
            // ($wire renvoie une fonction pour une propriété inconnue : on vérifie les types.)
            const component = window.Livewire?.all().find((c) => typeof c.$wire?.savedDataHash === 'string' && typeof c.$wire?.data === 'object');
            const dirty = !! component && !! window.jsMd5
                && window.jsMd5(JSON.stringify(component.$wire.data).replace(/\\/g, '')) !== component.$wire.savedDataHash;
            document.body.classList.toggle('ab-form-dirty', dirty);
        };
        const schedule = () => { clearTimeout(timer); timer = setTimeout(update, 150); };

        ['input', 'change', 'click', 'keyup'].forEach((type) => document.addEventListener(type, schedule, true));
        const hook = () => window.Livewire.hook('commit', ({ succeed }) => succeed(() => setTimeout(update, 50)));
        window.Livewire ? hook() : document.addEventListener('livewire:init', hook);
        document.addEventListener('livewire:navigated', schedule);
        schedule();
    })();
</script>
<style>
    /* Champs image : bouton « Remplacer » et zone de dépôt (resources/js/admin/images.js). */
    [data-image-upload] { position: relative; }
    [data-image-upload] .ab-replace-btn {
        display: none; position: absolute; z-index: 10; left: 50%; bottom: 12px; transform: translateX(-50%);
        padding: 6px 14px; border-radius: 999px; border: 0; background: rgba(21, 46, 71, .88); color: #fff; font-size: .8rem; font-weight: 600;
    }
    [data-image-upload].ab-has-image .ab-replace-btn { display: block; }
    [data-image-upload].ab-drop { outline: 3px dashed #1F4E8C; outline-offset: 4px; border-radius: 12px; }
    [data-image-upload].ab-drop .filepond--root { opacity: .6; }
</style>
