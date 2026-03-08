(function (blocks, element, blockEditor, components, serverSideRender) {
  var el = element.createElement;
  var TextControl = components.TextControl;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var ServerSideRender = serverSideRender;

  blocks.registerBlockType('biolink-pro/profile', {
    title: 'BioLink Profile',
    icon: 'admin-links',
    category: 'widgets',
    description: 'Embed a BioLink Pro profile on any page or post.',
    attributes: {
      username: { type: 'string', default: '' },
    },
    edit: function (props) {
      return el(
        element.Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: 'BioLink Settings', initialOpen: true },
            el(TextControl, {
              label: 'Username',
              value: props.attributes.username,
              onChange: function (val) { props.setAttributes({ username: val }); },
              help: 'Enter the BioLink profile username to embed.',
            })
          )
        ),
        props.attributes.username
          ? el(ServerSideRender, {
              block: 'biolink-pro/profile',
              attributes: props.attributes,
            })
          : el(
              'div',
              { style: { padding: '20px', textAlign: 'center', background: '#f0f0f0', borderRadius: '8px', color: '#666' } },
              el('p', null, 'BioLink Pro'),
              el('p', { style: { fontSize: '13px' } }, 'Enter a username in the block settings panel to embed a profile.')
            )
      );
    },
    save: function () {
      return null; // Server-side rendered
    },
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.serverSideRender
);
