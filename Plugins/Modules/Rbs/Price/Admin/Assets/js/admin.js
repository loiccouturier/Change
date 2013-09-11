(function ()
{
	"use strict";

	var app = angular.module('RbsChange');


	// Register default editors for 'Rbs_Price_BillingArea'.
	__change.createEditorForModel('Rbs_Price_BillingArea');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{

			$delegate.model('Rbs_Price')
				.route('home', 'Rbs/Price', { 'redirectTo': 'Rbs/Price/Price/'});

			$delegate.routesForModels([
				'Rbs_Price_Tax',
				'Rbs_Price_Price',
				'Rbs_Price_BillingArea'
			]);

			return $delegate;
		}]);

	}]);

	app.controller('rbsPriceBasePriceSelector', function ($scope) {
		if ($scope.document.sku)
		{
			$scope.priceBaseQuery= { "model": "Rbs_Price_Price",
				"where": {
					"and" : [
						{
							"op" : "eq",
							"lexp" : {
								"property" : "sku"
							},
							"rexp" : {
								"value": $scope.document.sku.id
							}
						},
						{
							"op" : "neq",
							"lexp" : {
								"property" : "id"
							},
								"rexp" : {
								"value": $scope.document.id
							}
						}
					]
				}
			};
		}
		else
		{
			$scope.priceBaseQuery= { "model": "Rbs_Price_Price"};
		}
	});
})();