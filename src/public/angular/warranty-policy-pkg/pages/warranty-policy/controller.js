app.component('warrantyPolicyList', {
    templateUrl: warranty_ploicy_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable1 = $('#warranty_policy').DataTable({
            stateSave: true,
            "dom": dom_structure,
            "language": {
                "search": "",
                "lengthMenu": "Rows Per Page _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            processing: true,
            serverSide: true,
            paging: true,
            ajax: {
                url: laravel_routes['getWarrantyPolicyList'],
                type: "GET",
                dataType: "json",
                data: function(d) {},
            },
            columns: [
                { data: 'action', class: 'action', searchable: false },
                { data: 'name', name: 'warranty_policies.name' },
                { data: 'status', searchable: false },
            ],
            "initComplete": function(settings, json) {
                $('.dataTables_length select').select2();
            },
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(total)
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }

        });
        /* Page Title Appended */
        $('.page-header-content .display-inline-block .data-table-title').html('Warranty Policy List <span class="badge badge-secondary" id="table_info">0</span>');
        $('.page-header-content .search.display-inline-block .add_close_button').html('<button type="button" class="btn btn-img btn-add-close"><img src="' + image_scr2 + '" class="img-responsive"></button>');
        $('.page-header-content .refresh.display-inline-block').html('<button type="button" class="btn btn-refresh"><img src="' + image_scr3 + '" class="img-responsive"></button>');
        $('.add_new_button').html(
            '<a href="#!/warranty-policy-pkg/warranty-policy/add" type="button" class="btn btn-secondary">' +
            'Add New' +
            '</a>'
        );

        $('.btn-add-close').on("click", function() {
            $('#warranty_policy').DataTable().search('').draw();
        });

        $('.btn-refresh').on("click", function() {
            $('#warranty_policy').DataTable().ajax.reload();
        });

        $scope.deleteCustomer = function($id) {
            $('#customer_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#customer_id').val();
            $http.get(
                warranty_ploicy_delete_data_url + '/' + $id,
            ).then(function(response) {
                if (response.data.success) {
                    $noty = new Noty({
                        type: 'success',
                        layout: 'topRight',
                        text: 'Warranty Policy Deleted Successfully',
                    }).show();
                    setTimeout(function() {
                        $noty.close();
                    }, 3000);
                    $('#warranty_policy_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/warranty-policy-pkg/warranty-policy/list');
                }
            });
        }
        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('warrantyPolicyForm', {
    templateUrl: warranty_ploicy_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        get_form_data_url = typeof($routeParams.id) == 'undefined' ? warranty_ploicy_get_form_data_url : warranty_ploicy_get_form_data_url + '/' + $routeParams.id;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        $http.get(
            get_form_data_url
        ).then(function(response) {
            // console.log(response);
            self.customer = response.data.customer;
            self.address = response.data.address;
            self.country_list = response.data.country_list;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                $scope.onSelectedCountry(self.address.country_id);
                $scope.onSelectedState(self.address.state_id);
            } else {
                self.state_list = [{ id: '', name: 'Select State' }];
                self.city_list = [{ 'id': '', 'name': 'Select City' }];
            }
        });

        /* Tab Funtion */
        $('.btn-nxt').on("click", function() {
            $('.cndn-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.cndn-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-pills').on("click", function() {
            tabPaneFooter();
        });
        $scope.btnNxt = function() {}
        $scope.prev = function() {}

        //SELECT STATE BASED COUNTRY
        $scope.onSelectedCountry = function(id) {
            $http.get(
                customer_get_state_list_data + '/' + id
            ).then(function(response) {
                // console.log(response);
                self.state_list = response.data.state_list;
            });
        }

        //SELECT CITY BASED STATE
        $scope.onSelectedState = function(id) {
            $http.get(
                customer_get_city_list_data + '/' + id
            ).then(function(response) {
                // console.log(response);
                self.city_list = response.data.city_list;
            });
        }

        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'code': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'cust_group': {
                    maxlength: 100,
                },
                'dimension': {
                    maxlength: 50,
                },
                'mobile_no': {
                    required: true,
                    minlength: 10,
                    maxlength: 25,
                },
                'email': {
                    required: true,
                    email: true,
                    minlength: 6,
                    maxlength: 255,
                },
                'address_line1': {
                    minlength: 3,
                    maxlength: 255,
                },
                'address_line2': {
                    minlength: 3,
                    maxlength: 255,
                },
                'pincode': {
                    required: true,
                    minlength: 6,
                    maxlength: 6,
                },
            },
            messages: {
                'code': {
                    maxlength: 'Maximum of 255 charaters',
                },
                'name': {
                    maxlength: 'Maximum of 255 charaters',
                },
                'cust_group': {
                    maxlength: 'Maximum of 100 charaters',
                },
                'dimension': {
                    maxlength: 'Maximum of 50 charaters',
                },
                'mobile_no': {
                    maxlength: 'Maximum of 25 charaters',
                },
                'email': {
                    maxlength: 'Maximum of 100 charaters',
                },
                'address_line1': {
                    maxlength: 'Maximum of 255 charaters',
                },
                'address_line2': {
                    maxlength: 'Maximum of 255 charaters',
                },
                'pincode': {
                    maxlength: 'Maximum of 6 charaters',
                },
            },
            invalidHandler: function(event, validator) {
                $noty = new Noty({
                    type: 'error',
                    layout: 'topRight',
                    text: 'You have errors,Please check all tabs'
                }).show();
                setTimeout(function() {
                    $noty.close();
                }, 3000)
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('#submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveCustomer'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            $noty = new Noty({
                                type: 'success',
                                layout: 'topRight',
                                text: res.message,
                            }).show();
                            setTimeout(function() {
                                $noty.close();
                            }, 3000);
                            $location.path('/warranty-policy-pkg/warranty-policy/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('#submit').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                $noty = new Noty({
                                    type: 'error',
                                    layout: 'topRight',
                                    text: errors
                                }).show();
                                setTimeout(function() {
                                    $noty.close();
                                }, 3000);
                            } else {
                                $('#submit').button('reset');
                                $location.path('/warranty-policy-pkg/warranty-policy/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('#submit').button('reset');
                        $noty = new Noty({
                            type: 'error',
                            layout: 'topRight',
                            text: 'Something went wrong at server',
                        }).show();
                        setTimeout(function() {
                            $noty.close();
                        }, 3000);
                    });
            }
        });
    }
});