// Starts CKEditor 5 on every <textarea data-editor>. Served from our own server (no CDN), so the strict CSP stays intact.
(function () {
  var C = window.CKEDITOR;
  if (!C) { return; }                       // editor failed to load: the plain textarea still works
  document.querySelectorAll('textarea[data-editor]').forEach(function (ta) {
    C.ClassicEditor.create(ta, {
      licenseKey: 'GPL',
      plugins: [C.Essentials, C.Paragraph, C.Heading, C.Bold, C.Italic, C.Underline, C.Strikethrough, C.Link, C.AutoLink, C.List,
                C.BlockQuote, C.Table, C.TableToolbar, C.HorizontalLine, C.Image, C.ImageCaption, C.ImageStyle, C.ImageToolbar,
                C.ImageUpload, C.SimpleUploadAdapter, C.PasteFromOffice, C.Autoformat],
      toolbar: { items: ['undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'underline', 'strikethrough', '|',
                         'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'uploadImage', 'insertTable', 'horizontalLine'],
                 shouldNotGroupWhenFull: true },
      heading: { options: [
        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
        { model: 'heading2', view: 'h2', title: 'Heading', class: 'ck-heading_heading2' },
        { model: 'heading3', view: 'h3', title: 'Sub-heading', class: 'ck-heading_heading3' },
        { model: 'heading4', view: 'h4', title: 'Small heading', class: 'ck-heading_heading4' } ] },
      link: { addTargetToExternalLinks: true, defaultProtocol: 'https://' },
      image: { toolbar: ['toggleImageCaption', 'imageTextAlternative', '|', 'imageStyle:inline', 'imageStyle:block', 'imageStyle:side'] },
      table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] },
      simpleUpload: { uploadUrl: ta.getAttribute('data-upload'), headers: { 'X-CSRF-Token': ta.getAttribute('data-csrf') } }
    }).catch(function (err) { console.error('Editor failed to start:', err); });
  });
})();
