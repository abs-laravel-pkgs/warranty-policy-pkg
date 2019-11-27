app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    //CUSTOMER
    when('/warranty-policy-pkg/warranty-policy/list', {
        template: '<warranty-policy-list></warranty-policy-list>',
        title: 'Sub Customers',
    }).
    when('/warranty-policy-pkg/warranty-policy/add', {
        template: '<warranty-policy-form></warranty-policy-form>',
        title: 'Add Sub Customer',
    }).
    when('/warranty-policy-pkg/warranty-policy/edit/:id', {
        template: '<warranty-policy-form></warranty-policy-form>',
        title: 'Edit Sub Customer',
    });
}]);