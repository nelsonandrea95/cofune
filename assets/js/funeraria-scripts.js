// Modal functionality
jQuery(document).ready(function($) {
    $(document).on('shown.bs.modal', function (event) {
        
        var button = $(event.relatedTarget);
        var paqueteId = button.data('paquete-id');
        var paqueteTitle = button.closest('.paquete-item').find('.title').text();
        var paquetePrice = button.closest('.paquete-item').find('.price').text();
        var modal = $(this);

        modal.find('.modal-title').text(paqueteTitle + ' - ' + paquetePrice);

        var data = {
            'action': 'get_contact_form_fields',
            'paquete_id': paqueteId,
            'nonce': funeraria_script_vars.nonce
        };

        $.post(funeraria_script_vars.ajaxurl, data, function (response) {
            if (response.success) {
                modal.find('.modal-body #funeraria-contact-form').html(response.data);
            } else {
                modal.find('.modal-body #funeraria-contact-form').html('<p>Error al cargar el formulario.</p>');
            }
        });



        // AJAX request to send the form data 
    $('#funeraria-contact-form').submit(function(e) {
        e.preventDefault(); 
        var servicioComplemetarios = [];
        $('input[name="servicio_complementarios_terms[]"]:checked').each(function() {
            servicioComplemetarios.push($(this).val());
        });
        var data = {
            'action': 'funeraria_save_solicitud',
            'nombre': $('#nombre').val(),
            'email': $('#email').val(),
            'telefono': $('#telefono').val(),
            'comentarios': $('#comentarios').val(),
            'paquete_id': $('#paquete-id').val(), 
            'servicio_complementarios_terms': servicioComplemetarios,
            'nonce': funeraria_script_vars.nonce
        };
        console.log(data);
        $.post(funeraria_script_vars.ajaxurl, data, function(response) {
            console.log(response);
            if (response.success) {
                Swal.fire({
                    title: '¡Éxito!',
                    text: response.data.message,
                    icon: 'success'
                }).then(function() {
                    $('#funeraria-contact-modal').modal('hide');
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.data.message,
                    icon: 'error'
                });
            }
        });
    });
    });

    
});

jQuery(document).ready(function($) {
    $('#funeraria-paquete-form #comuna').select2({
        placeholder: 'Seleccionar Comuna',
        allowClear: true 
    });
});

jQuery(document).ready(function($){

    $('#featured-image-upload').click(function(e) {
        e.preventDefault();
        var image = wp.media({
            title: 'Cargar Imagen Destacada',
            multiple: false,
            library: {
                type: 'image'
            },
        }).open()
        .on('select', function(e) {
            var uploaded_image = image.state().get('selection').first();
            var image_url = uploaded_image.toJSON().url;
            var image_id = uploaded_image.toJSON().id;
            $('#featured-image-preview').html('<img src="' + image_url + '" style="max-width:200px;" />');
            $('#featured_image').val(image_id);
        });
    });
});

