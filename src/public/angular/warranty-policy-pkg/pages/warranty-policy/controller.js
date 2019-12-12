app.component('warrantyPolicyList', {
    templateUrl: warranty_ploicy_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#warranty_policy').DataTable({
            stateSave: true,
            "dom": dom_structure,
            "language": {
                "search": "",
                "searchPlaceholder": "Search",
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
                { data: 'code', name: 'warranty_policies.code' },
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

        $scope.deleteWarrantyPolicy = function($id) {
            $('#warranty_policy_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#warranty_policy_id').val();
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
                    $('#warranty_policy').DataTable().ajax.reload(function(json) {});
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
            console.log(response);
            self.policy = response.data.warranty_policy;
            self.policy_details = response.data.warranty_policy_details;
            self.warranty_type_list = response.data.warranty_type_list;
            self.duration_type_list = response.data.duration_type_list;
            self.action = response.data.action;
            self.policy_detail_removal_id = [];
            if (self.action == 'Edit') {
                if (self.policy.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                $scope.add_policy_details();
                self.switch_value = 'Active';
            }
            $rootScope.loading = false;
        });

        /* Tab Funtion */
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-pills').on("click", function() {
            tabPaneFooter();
        });
        $scope.btnNxt = function() {}
        $scope.prev = function() {}

        //ADD POLICY DETAILS 
        $scope.add_policy_details = function() {
            self.policy_details.push({});
        }

        //REMOVE POLICY DETAILS 
        $scope.removePolicy = function(index, id) {
            console.log(id, index);
            if (id) {
                self.policy_detail_removal_id.push(id);
                $('#policy_detail_removal_id').val(JSON.stringify(self.policy_detail_removal_id));
            }
            self.policy_details.splice(index, 1);
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
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveWarrantyPolicy'],
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
                                $('.submit').button('reset');
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
                                $('.submit').button('reset');
                                $location.path('/warranty-policy-pkg/warranty-policy/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
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