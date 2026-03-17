/**
 * JS for metis list table
 *
 * @package     vgw-metis
 * @copyright   Verwertungsgesellschaft Wort
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 * @author      Torben Gallob
 * @author      Michael Hillebrand
 */
(function ($) {
        'use strict';

        if (!wp_metis_list_table_obj.columns) {
            console.error('No wp_metis_list_table_obj.columns are defined');
            return;
        }

        if (!wp_metis_list_table_obj.upsert_action) {
            console.error('No wp_metis_list_table_obj.upsert_action is defined');
            return;
        }

        if (!wp_metis_list_table_obj.upsert_action) {
            console.error('No wp_metis_list_table_obj.delete_action is defined');
            return;
        }

        let columns = JSON.parse(wp_metis_list_table_obj.columns);
        const upsert_action = wp_metis_list_table_obj.upsert_action;
        const delete_action = wp_metis_list_table_obj.delete_action;
        $(window).load(function () {
            //turns to edit
            $('body').on('click', '.metis_row_edit', function () {
                $(this).parent().siblings('td.metis-col-data').each(function () {
                    let value = $(this).attr('metis-col-data-value');
                    let key = $(this).attr('metis-col-data-key');

                    let edit_type = columns[key]['edit_type'];
                    if (edit_type === 'INPUT') {
                        // Create input element safely to prevent XSS
                        let input = $('<input>', {
                            id: columns[key]['name'],
                            class: 'metis_edit_col',
                            type: columns[key]["field_type"],
                            value: value,  // jQuery automatically escapes this
                            maxlength: columns[key]['max'],
                            'metis-col-data-key': key
                        });
                        let errorP = $('<p>', {
                            style: 'display: none;color: red',
                            id: 'metis-col-error-' + key,
                            text: 'ERROR'
                        });
                        $(this).empty().append(input).append(errorP);
                    }

                    if (edit_type === 'SELECT') {
                        let select_options = columns[key]['select_options'];
                        
                        // Create select element safely to prevent XSS
                        let select = $('<select>', {
                            id: columns[key]['name'],
                            class: 'metis_edit_col',
                            'metis-col-data-key': key
                        });

                        // add options to select from column definition
                        Object.keys(columns[key]['select_options']).forEach(option => {
                            let optionElement = $('<option>', {
                                value: option,
                                text: select_options[option]
                            });
                            if (value === option) {
                                optionElement.prop('selected', true);
                            }
                            select.append(optionElement);
                        });
                        
                        let errorP = $('<p>', {
                            style: 'display: none;color: red',
                            id: 'metis-col-error-' + key,
                            text: 'ERROR'
                        });

                        $(this).empty().append(select).append(errorP);
                    }
                });

                // no other actions
                $(this).siblings('.metis_row_save').show();
                $(this).siblings('.metis_row_cancel').show();
                $('.metis_row_edit').each(function () {
                    $(this).hide();
                })
                $('.metis_row_delete').each(function () {
                    $(this).hide();
                })
                $('#metis_add_row').prop('disabled', true);


                $(this).hide();
            });

            $('body').on('click', '.metis_row_save', function () {
                let data = {};
                data['id'] = $(this).attr('data-row-id');

                $('input').each(function () {
                    data[$(this).attr('metis-col-data-key')] = $(this).val();
                });

                $('select').each(function () {
                    data[$(this).attr('metis-col-data-key')] = $(this).val();
                });

                if (validate_before_save(data) === true) {
                    save_inline_data(data);
                    change_fields_to_tr_text();
                } else {
                    alert('Fehler in der Eingabe');
                    return false;
                }

                // show all action after save
                $(this).siblings('.metis_row_edit').show();
                $(this).siblings('.metis_row_cancel').hide();
                $('.metis_row_edit').each(function () {
                    $(this).show();
                })
                $('.metis_row_delete').each(function () {
                    $(this).show();
                });
                $('#metis_add_row').prop('disabled', false);

                $(this).hide();
            });


            // action delete
            $('body').on('click', '.metis_row_delete', function () {
                if (confirm('Soll der Eintrag wirklich gelöscht werden?') === true)
                    delete_data($(this).attr('data-row-id'));
            })

            // action cancel
            $('body').on('click', '.metis_row_cancel', function () {
                location.reload();
            })


            // on change of a input field will be validated
            $('body').on('blur', '.metis_edit_col', function () {
                validate_data($(this).val(), $(this).attr('metis-col-data-key'));
            });

            // Adds a new row
            $('body').on('click', '#metis_add_row', function () {
                let new_tr = $('<tr>');

                // for all columns in definition
                Object.keys(columns).forEach(key => {
                    let td = $('<td>');

                    // add column if input
                    if (columns[key]['edit_type'] === 'INPUT') {
                        let input = $('<input>', {
                            id: columns[key]['name'],
                            class: 'metis_edit_col',
                            type: columns[key]["field_type"],
                            value: '',
                            'metis-col-data-key': key,
                            maxlength: columns[key]['max']
                        });
                        let errorP = $('<p>', {
                            style: 'display: none;color:red',
                            id: 'metis-col-error-' + key,
                            text: 'ERROR'
                        });
                        td.append(input).append(errorP);
                    }
                    
                    // add column if select
                    if (columns[key]['edit_type'] === 'SELECT') {
                        let select = $('<select>', {
                            id: columns[key]['name'],
                            class: 'metis_edit_col',
                            'metis-col-data-key': key
                        });
                        
                        let select_options = columns[key]['select_options'];
                        Object.keys(columns[key]['select_options']).forEach(option => {
                            let optionElement = $('<option>', {
                                value: option,
                                text: select_options[option]
                            });
                            select.append(optionElement);
                        });
                        
                        let errorP = $('<p>', {
                            style: 'display: none;color:red',
                            id: 'metis-col-error-' + key,
                            text: 'ERROR'
                        });
                        td.append(select).append(errorP);
                    }
                    
                    new_tr.append(td);
                });
                
                // Add action column
                let actionTd = $('<td>');
                let saveLink = $('<a>', {
                    href: '#',
                    class: 'metis_row_save',
                    'data-row-id': '',
                    text: 'speichern |'
                });
                let cancelLink = $('<a>', {
                    href: '#',
                    class: 'metis_row_cancel',
                    'data-row-id': '',
                    text: ' abbrechen'
                });
                actionTd.append(saveLink).append(cancelLink);
                new_tr.append(actionTd);

                $('table').find('tbody').append(new_tr);

                // only the save action for new row - other will be hidden
                $('.metis_row_edit').each(function () {
                    $(this).hide();
                })
                $('.metis_row_delete').each(function () {
                    $(this).hide();
                })
                $('#metis_add_row').prop('disabled', true);
            });
        });


        // Change from edit fields to text fields for all inputs and selects
        // Removes the error section
        function change_fields_to_tr_text() {
            $('input.metis_edit_col').each(function () {
                if ($(this).val() != "")
                    $(this).text($(this).val());  // Use .text() instead of .html() to prevent XSS
                else
                    $(this).text(" ");
                $('#metis-col-error-' + $(this).attr('metis-col-data-key')).remove();
                $(this).contents().unwrap();
            });

            $('select.metis_edit_col').each(function () {
                // geht label not key for options
                $(this).text(columns[$(this).attr('metis-col-data-key')]['select_options'][$(this).val()]);  // Use .text() instead of .html() to prevent XSS
                $('#metis-col-error-' + $(this).attr('metis-col-data-key')).remove();
                $(this).contents().unwrap();
            });
        }

        // Save the current row with given data
        // If no id is given data will be inserted otherwise updated
        // reloades on insert new row because of new id
        function save_inline_data(data) {
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: '/wp-admin/admin-ajax.php',
                data: {
                    action: upsert_action, 
                    data: data,
                    _wpnonce: wp_metis_list_table_obj.save_nonce
                },
                success: function (response) {
                    location.reload();
                    return true;
                },
                error: function(xhr, status, error) {
                    console.error('Save failed:', status, error);
                    console.error('Response:', xhr.responseText);
                    alert('Fehler beim Speichern: ' + error);
                }
            });

        }

        // Deletes row with given id
        function delete_data(id) {
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: '/wp-admin/admin-ajax.php',
                data: {
                    action: delete_action, 
                    id: id,
                    _wpnonce: wp_metis_list_table_obj.delete_nonce
                },
                success: function (response) {
                    if (response == 0)
                        location.reload();
                    else {
                        if (response.success == false)
                            alert(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete failed:', status, error);
                    console.error('Response:', xhr.responseText);
                    alert('Fehler beim Löschen: ' + error);
                }
            });
        }

        // validates given data
        function validate_before_save(data) {
            let valide = true;
            // validate - needed when new data row and fields are never touched
            Object.keys(columns).forEach(key => {
                validate_data(data[key], key);
            });

            Object.keys(columns).forEach(key => {
                if (columns[key]['valid'] !== undefined && columns[key]['valid'] === false)
                    valide = false;
            });
            return valide;
        }


        // validate value with the given key from columns defintion array
        function validate_data(value, key) {
            columns[key]['valid'] = false;

            if (value === null || value === undefined || key === null || key === undefined)
                return false;

            if (columns[key]['required'] === true) {
                if (value === '') {
                    $('#metis-col-error-' + key).show();
                    $('#metis-col-error-' + key).html(columns[key]['label'] + ' ist ein Pflichtfeld!');
                    return false;
                }
            }

            if (columns[key]['field_type'] === 'number') {
                if (value != '') {
                    if (value > columns[key]['max'] && columns[key]['max'] >= 0) {
                        $('#metis-col-error-' + key).show();
                        $('#metis-col-error-' + key).html(columns[key]['label'] + ' darf maximal 7 Stellen haben.');
                        return false;
                    }
                    if (value < columns[key]['min'] && columns[key]['min'] >= 0) {
                        $('#metis-col-error-' + key).show();
                        $('#metis-col-error-' + key).html(columns[key]['label'] + ' darf nicht 0 oder negativ sein.');
                        return false;
                    }
                }
            }
            if (columns[key]['field_type'] === 'text') {
                if (String(value).length > columns[key]['max'] && columns[key]['max'] > 0) {
                    $('#metis-col-error-' + key).show();
                    $('#metis-col-error-' + key).html(columns[key]['label'] + ' zu lange maximal ' + columns[key]['max'] + ' Zeichen');
                    return false;
                }
                if (String(value).length < columns[key]["min"] && columns[key]['min'] > 0) {
                    $('#metis-col-error-' + key).show();
                    $('#metis-col-error-' + key).html(columns[key]['label'] + ' zu kurz minimal ' + columns[key]['min'] + " Zeichen");
                    return false;
                }
            }

            // Everthing is ok
            $('#metis-col-error-' + key).hide();
            $('#metis-col-error-' + key).html('');
            columns[key]["valid"] = true;

            return true;
        }
    }

)
(jQuery);
