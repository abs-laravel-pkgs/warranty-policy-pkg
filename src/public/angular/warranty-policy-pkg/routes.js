app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    //CUSTOMER
    when('/warranty-policy-pkg/warranty-policy/list', {
        template: '<warranty-policy-list></warranty-policy-list>',
        title: 'Warranty Policies',
    }).
    when('/warranty-policy-pkg/warranty-policy/add', {
        template: '<warranty-policy-form></warranty-policy-form>',
        title: 'Add Warranty Policy',
    }).
    when('/warranty-policy-pkg/warranty-policy/edit/:id', {
        template: '<warranty-policy-form></warranty-policy-form>',
        title: 'Edit Warranty Policy',
    });
}]);