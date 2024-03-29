jQuery(function ($) {
    $('.datatable').DataTable({
        ajax: "/ajax/v2_get_customers",
        processing: true,
        serverSide: true,
        columns: [
            {
                orderable: false,
                data: function (row) {
                    var v = `<span>${row.EmployeeNumber}</span>`;

                    if(row.status == 1) {
                        v += `<a href="#" title="Not updated on Shopify">
                                <svg fill="none" viewBox="0 0 24 24" class="w-8 h-8 text-red"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </a>`;
                    }

                    return v;
                }
            },
            {
                orderable: false,
                data: function (row) {
                    return `${row.FirstName} ${row.LastName}`;
                }
            },
            {
                orderable: false,
                data: "PhysicalLocation"
            },
            {
                orderable: false,
                data: "EmailAddress"
            },
            {
                orderable: false,
                data: "EpikAllotment"
            },
            {
                orderable: false,
                data: "UniformGearAuthority"
            },
            {
                orderable: false,
                data: "last_updated"
            },
            {
                orderable: false,
                data: function (row) {
                    var btn = row.shopify_user_id ? "Update on Shopify" : "Create on Shopify";
                    return `<div style="width: 200px;">
                            <div><a href="" data-id="${row.id}"
                                    class="a_send_shopify flex w-full justify-center rounded-md bg-indigo-600 my-2 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">${btn}</a>
                            </div>
                            <div>
                                <a class="flex w-full justify-center rounded-md bg-indigo-600 my-2 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                   target="_blank" href="/logs?id=${row.id}">View
                                    Sync Logs</a></div>
                            <div>
                                <a class="flex w-full justify-center rounded-md bg-indigo-600 my-2 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                   target="_blank" href="/orders?id=${row.id}">View
                                    Orders</a></div>
                        </div>`;
                }
            },
        ]
    });

    $('#form').submit(function (e) {
        try {
            var formData = new FormData(this);
            $(this).find('[type=submit]').text('Uploading...').attr('disabled', true);

            $.ajax({
                url: window.ajx_url + "/upload_csv",
                data: formData,
                type: 'POST',
                contentType: false,
                processData: false,
                dataType: 'JSON',
                success: function (res) {
                    if (res.status) {
                        window.location.reload();
                    }
                    $(this).find('[type=submit]').text('Upload').removeAttr('disabled');
                },
                error: function (err) {
                    console.error(err);
                    $(this).find('[type=submit]').text('Upload').removeAttr('disabled');
                }
            });
        } catch (e) {
            console.error(e);
        }

        return false;
    });

    $(document).on('click', '.a_send_shopify', function (e) {
        e.preventDefault();

        var id = $(this).data('id');
        var btn = $(this);

        btn.attr('data-text', btn.text());
        btn.text('Please wait...').attr('disabled', true);

        $.post(window.ajx_url + "/send_shopify", {id: id}, function (res) {
            if (res.status) {
                btn.text(btn.attr('data-text'));
            } else {
                btn.text(btn.data('text'));
            }
            btn.removeAttr('disabled');
        }, 'json');
    });

    $(document).on('click', '#btn-run-all', function (e) {
        e.preventDefault();

        // var i;
        // $('.a_send_shopify').each(function (e) {
        //     i++;
        //     $(this).trigger('click');
        //
        //     if(i > 10)
        //         i = 0;
        // })
    });
});
