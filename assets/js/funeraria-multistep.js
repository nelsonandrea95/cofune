jQuery(document).ready(function ($) {
    let currentStep = 1;
    const $form = $('#funeraria-multistep-form');
    const $steps = $form.find('.step');
    const $stepButtons = $form.find('.step-button');

    // Show the first step initially
    showStep(currentStep);

    // Function to show a specific step
    function showStep(step) {
        $steps.hide();
        $form.find('[data-step="' + step + '"]').show();

        // Update step button styles
        $stepButtons.removeClass('active');
        $form.find('.step-button[data-step="' + step + '"]').addClass('active'); 

        currentStep = step;
        
    }

    // Next step button handler
    $form.on('click', '.next-step', function () {
        if (validateStep(currentStep)) {
            showStep(currentStep + 1);
            $form.find('.step-button[data-step="' + currentStep + '"]').addClass('completed'); 
            
        }
    });

    $form.on('click', '.step-button', function() {
        const requestedStep = parseInt($(this).data('step'));

        if ($(this).hasClass('completed') || requestedStep === 1) {
            showStep(requestedStep);
        }
    });

    // Event listener for changes in radio buttons and select elements
    $form.on('change', 'input[type="radio"], select', function() {
        if (validateStep(currentStep)) {
            showStep(currentStep + 1); 
            $form.find('.step-button[data-step="' + currentStep + '"]').addClass('completed'); 
        }
    });

    // Function to validate the current step (add your validation logic here)
    function validateStep(step) {
        if (step === 2 && !$form.find('input[name="tipo_servicio"]:checked').length) {
            alert('Por favor, seleccione un servicio funerario.');
            return false;
        } else if (step === 3 && !$form.find('input[name="sala_velatorio"]:checked').length) {
            alert('Por favor, indique si desea sala velatorio.');
            return false;
        } else if (step === 4 && !$('#region').val()) {
            alert('Por favor, seleccione una región.');
            return false;
        } else if (step === 5 && !$('#comuna').val()) {
            alert('Por favor, seleccione una comuna.');
            return false;
        }
        return true;
    }

    $('#region').select2({
        placeholder: 'Seleccionar Region',
        allowClear: true
    });

    // AJAX request to populate comunas dropdown when region is changed
    $('#region').change(function () {
        // Retrieve nonce from a data attribute (we'll add it in the HTML)
        var nonce = funeraria_vars.nonce_obtener_comunas;
        var regionId = $(this).val();
        var data = {
            'action': 'obtener_comunas_por_region',
            'region_id': regionId,
            'nonce': nonce
        };

        $.post(funeraria_vars.ajaxurl, data, function (response) {
            if (response.success) {
                $('#comuna').html(response.data);
                $('#comuna').select2({
                    placeholder: 'Seleccionar Comuna',
                    allowClear: true
                });
            } else {
                $('#comuna').html('<option value="">Error al obtener comunas.</option>');
            }
        });
    });
    
    // AJAX request to get paquetes funerarios based on selected options
    $form.on('change', '#comuna', function (e) {
        e.preventDefault();
        var nonce = funeraria_vars.nonce_buscar_paquetes;

        var data = {
            'action': 'buscar_paquetes_funerarios',
            'tipo_servicio': $('input[name="tipo_servicio"]:checked').val(),
            'sala_velatorio': $('input[name="sala_velatorio"]:checked').val(),
            'region': $('#region').val(),
            'comuna': $('#comuna').val(),
            'nonce': nonce  
        };

        $.post(funeraria_vars.ajaxurl, data, function (response) {
            if (response.success) {
                $('#funeraria-resultados').html(response.data);
                paqueteModal()
            } else {
                $('#funeraria-resultados').html('<p>No se encontraron paquetes funerarios que coincidan con su selección.</p>');
            }
        });
    });

    function paqueteModal(){
        $('.archive-paquete-link').click(function(e) {
            //console.log('hola');
            e.preventDefault();
            var post_id = $(this).data('postid'); 

            $.ajax({
                url: funeraria_script_vars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'funeraria_get_paquete_content',
                    post_id: post_id,
                    'nonce': funeraria_script_vars.get_paquete_content_nonce
                },
                success: function(response) {
                    console.log(response);
                    if (response.success) {
                        $('#modal-content').html(response.data);
                        $('#modalPaqueteContent').modal('show');
                    } else {
                        alert(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
        });
    }
});
