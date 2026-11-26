@php
    $template = $template ?? null;
    $channels = old('channels', $template ? $template->channelList() : ['database']);
    $channels = is_array($channels) ? $channels : ['database'];
    $variablesValue = old('variables', $template ? implode(', ', $template->variableList()) : '');
@endphp

<div class="mb-3">
  <label class="form-label" for="tplEventKey">Event key</label>
  <input type="text" id="tplEventKey" name="event_key" class="form-control font-monospace"
         list="notification-event-keys"
         value="{{ old('event_key', $template?->event_key ?? '') }}"
         placeholder="e.g. order.shipped">
  <datalist id="notification-event-keys">
    @foreach(\App\Support\NotificationTemplateCatalog::all() as $catalogKey => $definition)
      <option value="{{ $catalogKey }}">{{ $definition['name'] }}</option>
    @endforeach
  </datalist>
</div>

<div class="mb-3">
  <label class="form-label" for="tplDescription">Description</label>
  <input type="text" id="tplDescription" name="description" class="form-control"
         value="{{ old('description', $template?->description ?? '') }}"
         placeholder="When and to whom this notification is sent">
</div>

<div class="row">
  <div class="col-md-6 mb-3">
    <label class="form-label" for="tplName">Name</label>
    <input type="text" id="tplName" name="name" class="form-control"
           value="{{ old('name', $template?->name ?? '') }}">
  </div>
  <div class="col-md-6 mb-3">
    <label class="form-label" for="tplTitle">Title</label>
    <input type="text" id="tplTitle" name="title" class="form-control"
           value="{{ old('title', $template?->title ?? '') }}">
  </div>
</div>

<div class="mb-3">
  <label class="form-label" for="tplBody">Body</label>
  <input type="hidden" id="tplBody" name="body" value="{{ old('body', $template?->body ?? '') }}">
  <div id="tpl-body-editor"
       class="js-quill-editor notification-body-editor"
       data-quill-theme="snow"
       data-quill-toolbar-preset="full"
       data-quill-target="#tplBody"
       data-quill-placeholder="Write the notification body. Variables like {customer_name} are replaced on send."
       data-quill-initial-html="{{ e((string) old('body', $template?->body ?? '')) }}"></div>
</div>

<div class="mb-3">
  <label class="form-label d-block">Channels</label>
  <div class="form-check form-check-inline">
    <input class="form-check-input" type="checkbox" name="channels[]" id="channelDatabase" value="database"
           @checked(in_array('database', $channels, true))>
    <label class="form-check-label" for="channelDatabase">In-app inbox</label>
  </div>
  <div class="form-check form-check-inline">
    <input class="form-check-input" type="checkbox" name="channels[]" id="channelMail" value="mail"
           @checked(in_array('mail', $channels, true))>
    <label class="form-check-label" for="channelMail">Email</label>
  </div>
</div>

<div class="mb-3">
  <label class="form-label" for="tplVariables">Variables</label>
  <input type="text" id="tplVariables" name="variables" class="form-control font-monospace"
         value="{{ $variablesValue }}"
         placeholder="customer_name, order_number">
</div>

<div class="form-check mb-3">
  <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
         @checked(old('is_active', $template?->is_active ?? true))>
  <label class="form-check-label" for="is_active">Active</label>
</div>
