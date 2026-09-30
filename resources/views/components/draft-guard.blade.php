@props(['savedText' => null])
<div {{ $attributes }} x-data="{
    initialText: @js($savedText),
    saved: '',
    submitting: false,
    values(initial = false) { return JSON.stringify([...this.$el.querySelectorAll('textarea, input:not([type=hidden]), select')].map(el => initial && this.initialText !== null && el.tagName === 'TEXTAREA' ? this.initialText : (el.type === 'checkbox' ? el.checked : el.value))); },
    capture() { this.$nextTick(() => { this.saved = this.values(); this.submitting = false; }); },
    dirty() { return !this.submitting && this.saved !== this.values(); },
    init() { this.$nextTick(() => { this.saved = this.values(true); }); }
}"
@task-context-changed.window="history.replaceState(history.state, '', $event.detail.url)"
@draft-saved.window="capture()"
@draft-context-loaded.window="capture()"
@beforeunload.window="if (dirty()) { $event.preventDefault(); $event.returnValue = ''; }"
@click.capture="const button = $event.target.closest('button'); const action = button?.getAttribute('wire:click') || ''; if (/^(newDraft|newAssignmentDraft|openDraft|editAssignmentDraft|startNewVersion)/.test(action) && dirty() && !confirm('Discard unsaved changes? Choose Cancel to stay and save your draft.')) { $event.preventDefault(); $event.stopImmediatePropagation(); }">
    {{ $slot }}
</div>
