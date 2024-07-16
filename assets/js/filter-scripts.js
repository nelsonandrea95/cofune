jQuery(document).ready(function ($) {

    
    $("#min-value").text("$" + funeraria_vars.min_price);
    $("#max-value").text("$" + funeraria_vars.max_price);

    var initialMinPrice = parseInt(funeraria_vars.min_price);
    var initialMaxPrice = parseInt(funeraria_vars.max_price);
    
    $("#price-range").slider({
        range: true,
        min: initialMinPrice,
        max: initialMaxPrice,
        values: [initialMinPrice, initialMaxPrice], 
        slide: function (event, ui) {
            $("#min-value").text("$" + ui.values[0]);
            $("#max-value").text("$" + ui.values[1]);
            $("#min_price").val(ui.values[0]);
            $("#max_price").val(ui.values[1]);
        },
        change: function (event, ui) {
            $('#reset-filters').removeClass('d-none');
            applyFilters()
        }
    });

    $('#reset-filters').click(function (e) {
        e.preventDefault();

        $('#funeraria-filter-form')[0].reset();

        $("#price-range").slider("values", [initialMinPrice, initialMaxPrice]);
        $("#min_price").val(initialMinPrice);
        $("#max_price").val(initialMaxPrice);
        $("#min-value").text("$" + initialMinPrice);
        $("#max-value").text("$" + initialMaxPrice);

        $("#filter-comuna").empty().append('<option value="">Seleccionar Comuna</option>');
        $('#filter-region').trigger('change');

        $('.service-item').removeClass('selected');
        applyFilters()
        $('#reset-filters').addClass('d-none');
    });

    function getSelectedServices() {
        return $('.service-item.selected').map(function() {
            return $(this).data('slug');
        }).get();
    }

    $('.services-container').on('click', '.service-item', function() {
        $(this).toggleClass('selected');

        applyFilters()
        $('#reset-filters').removeClass('d-none');
    });

    $('.btn-rate').click(function(e){
        e.preventDefault();
        $(this).toggleClass('selected');
        rate = $(this).data('rate');
        applyFilters(rate)
        $('#reset-filters').removeClass('d-none');
    })

    $('#sort-by').change(function() {
        applyFilters();
    });

    function applyFilters(rate) {
        var nonce = funeraria_vars.nonce_buscar_paquetes; 
        
        var data = {
            'action': 'filter_paquetes_funerarios',
            'min_price': $('#min_price').val(),
            'max_price': $('#max_price').val(),
            'region': $('#filter-region').val(),
            'comuna': $('#filter-comuna').val(),
            'servicios[]': getSelectedServices(),
            'rate': rate,
            'orderby': $('#sort-by').val(),
            'nonce': nonce
        };
        console.log(data);
        $.post(funeraria_vars.ajaxurl, data, function (response) {
            console.log(response);
            if (response.success) {
                $('#funeraria-package-results').html(response.data.html);
                $('#number-of-results').text(response.data.total_posts);
                paqueteModal()
            } else {
                $('#funeraria-package-results').html('<p>No se encontraron paquetes funerarios que coincidan con su selección123.</p>');
            }
        });
        
    };

    $('#filter-region').change(function() {
        var regionId = $(this).val();
        var data = {
            'action': 'obtener_comunas_por_region',
            'region_id': regionId,
            'nonce': funeraria_vars.nonce_obtener_comunas
        };
        $.post(funeraria_vars.ajaxurl, data, function (response) {
            if (response.success) {
                $('#filter-comuna').html(response.data);
            } else {
                $('#filter-comuna').html('<option value="">Error al obtener comunas.</option>');
            }
        });
    });

    $('#filter-comuna').change(function() {
        applyFilters()
        $('#reset-filters').removeClass('d-none');
    })

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
    paqueteModal()
});



