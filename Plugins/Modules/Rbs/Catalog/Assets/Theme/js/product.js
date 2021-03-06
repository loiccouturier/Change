(function(jQuery) {
	"use strict";

	var app = angular.module('RbsChangeApp');

	function RbsCatalogProductItemController(scope, $element, $http, $compile, $rootScope, $window, AjaxAPI) {
		scope.productData = {};
		var cacheKey = $element.attr('data-cache-key');
		if (cacheKey && angular.isObject($window['__change']) && $window['__change'][cacheKey]) {
			scope.productData = $window['__change'][cacheKey];
		}

		this.setPictograms = function(productData) {
			scope.pictograms = null;
			if (productData && productData.common && productData.common.attributes && productData.common.attributes.pictograms) {
				var attr = productData.attributes[productData.common.attributes.pictograms];
				if (attr && attr.value && attr.value.length) {
					scope.pictograms = attr.value;
				}
			}
		};

		this.setPictograms(scope.productData);

		scope.addLine = function() {
			addLine(scope, $http, $compile, $rootScope, $window, AjaxAPI);
		};
	}
	RbsCatalogProductItemController.$inject = ['$scope', '$element', '$http', '$compile', '$rootScope', '$window', 'RbsChange.AjaxAPI'];
	app.controller('RbsCatalogProductItemController', RbsCatalogProductItemController);

	function rbsCatalogProductsList(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogProductsList.tpl',
			scope: {},
			controller: ['$scope', '$element', function (scope, elem) {
				var cacheKey = elem.attr('data-cache-key');
				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
				var data = AjaxAPI.globalVar(cacheKey);
				scope.productsData = data.productsData;
				scope.contextData = data.context.data;
				scope.context = {URLFormats : data.context.URLFormats, pagination: data.context.pagination, visualFormats: []};
				angular.forEach(data.context.visualFormats, function(size, name) {
					scope.context.visualFormats.push(name);
				})
			}],
			link: function(scope, elem, attrs) {
				scope.loading = false;
				scope.viewDetailTitleMask = attrs.viewDetailTitleMask || 'PRODUCT_TITLE';

				scope.addMoreProducts = function() {
					scope.context.pagination.offset = scope.productsData.length;
					scope.loading = true;
					AjaxAPI.getData('Rbs/Catalog/Product/', scope.contextData, scope.context)
						.success(function(data) {
							if (data.pagination.offset == scope.productsData.length) {
								angular.forEach(data.items, function(product) {
									scope.productsData.push(product);
								})
							}
							scope.loading = false;
						})
				};

				scope.scrollDisabled = function() {
					var productCount = parseInt(attrs.productCount);
					return scope.loading || scope.productsData.length >= productCount;
				}
			}
		}
	}

	rbsCatalogProductsList.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsCatalogProductsList', rbsCatalogProductsList);

	function rbsCatalogProductItemData($http, $compile, $rootScope, $window, AjaxAPI) {
		return {
			restrict: 'A',
			link: function (scope, elem, attrs) {
				var productData = scope.productData;

				scope.pictograms = null;
				scope.url = null;
				scope.visual = null;
				scope.viewDetailTitle = null;

				if (!productData || !productData.common) {
					return;
				}

				scope.viewDetailTitle = scope.viewDetailTitleMask.replace('PRODUCT_TITLE', productData.common.title);

				if (productData.common.attributes && productData.common.attributes.pictograms) {
					var attr = productData.attributes[productData.common.attributes.pictograms];
					if (attr && attr.value && attr.value.length) {
						scope.pictograms = attr.value;
					}
				}

				if (productData.common.URL) {
					scope.url = productData.common.URL.contextual || productData.common.URL.canonical;
				}

				if (productData.common.visuals && productData.common.visuals.length) {
					scope.visual = productData.common.visuals[0];
				}

				scope.addLine = function() {
					addLine(scope, $http, $compile, $rootScope, $window, AjaxAPI);
				};
			}
		}
	}

	rbsCatalogProductItemData.$inject = ['$http', '$compile', '$rootScope', '$window', 'RbsChange.AjaxAPI'];
	app.directive('rbsCatalogProductItemData', rbsCatalogProductItemData);

	function rbsCatalogAddListItemProductToCart() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogAddListItemProductToCart.tpl',
			replace: false,
			scope: false,
			link: productDataLink
		}
	}
	app.directive('rbsCatalogAddListItemProductToCart', rbsCatalogAddListItemProductToCart);

	function productDataLink(scope, elm, attrs) {
		scope.$watch('productData', function(productData) {
			if (productData && productData.cart) {
				var cart = productData.cart;
				if (attrs.hasOwnProperty('productQuantity')) {
					cart.quantity = parseInt(attrs['productQuantity']);
				}
				else {
					cart.quantity = (cart.minQuantity) ? cart.minQuantity : 1;
				}
				if (attrs.modalId) {
					cart.modalId = attrs.modalId;
				}
			}
		});
	}

	function addLine(scope, $http, $compile, $rootScope, $window, AjaxAPI) {
		var productData = scope.productData;
		if (productData && productData.cart && productData.cart.key) {
			var data = {productId: productData.common.id, quantity: productData.cart.quantity};
			var modalId = productData.cart.modalId;
			if (modalId) {
				data.sectionPageFunction = 'Rbs_Catalog_ProductAddedToCart';
				if (angular.isObject($window['__change']) && angular.isObject($window['__change']['navigationContext'])) {
					var navigationContext = $window['__change']['navigationContext'];
					data.themeName = navigationContext.themeName;
				}
			}

			scope.modalContentLoading = true;

			var cartParams = {
				detailed: false,
				URLFormats: 'canonical',
				visualFormats: 'shortCartItem'
			};

			var request = AjaxAPI.putData('Rbs/Commerce/Cart', {addProducts:[data]}, cartParams);
			request.success(function(resultData) {
					var cart = resultData.dataSets;
					$rootScope.$broadcast('rbsRefreshCart', {'cart': cart});
					if (modalId && cart.updated && cart.updated.addProducts && cart.updated.addProducts.length) {
						var addData = cart.updated.addProducts[0];
						if (addData['modalContentUrl']) {
							var mainContentElement = jQuery('#' + modalId + ' .modal-main-content');
							mainContentElement.html('<div class="text-center"><img alt="" src="data:image/gif;base64,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" /></div>');
							scope.hideModalContent = false;
							$http.get(addData['modalContentUrl'])
								.success(function(resultData2) {
									jQuery('#' + modalId + ' .modal-loading').hide();
									mainContentElement.html(resultData2);
									$compile(mainContentElement.contents())(scope);
									mainContentElement.show();
									scope.modalContentLoading = false;
								})
								.error(function(data, status, headers) {
									scope.hideModalContent = true;
									scope.modalContentLoading = false;
									console.log('error', data, status, headers);
								});
						}
						else {
							scope.hideModalContent = true;
						}
						jQuery('#' + modalId).modal({});
					}
				})
				.error(function(data, status, headers) {
					console.log('error', data, status, headers);
				});
		}
	}

	function extractPictogram(productData) {
		if (productData && productData.common && productData.common.attributes && productData.common.attributes.pictograms) {
			var attr = productData.attributes[productData.common.attributes.pictograms];
			if (attr && attr.value && attr.value.length) {
				return attr.value;
			}
		}
		return null;
	}

	function rbsCatalogProductPictograms() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogProductPictograms.tpl',
			replace: false,
			scope: { pictograms: '=', pictogramFormat: '=' },
			link: function(scope, elm, attrs) {

			}
		}
	}

	app.directive('rbsCatalogProductPictograms', rbsCatalogProductPictograms);

	function extractVisuals(productData) {
		if (productData && productData.common && productData.common.visuals) {
			var visuals = productData.common.visuals;
			if (visuals && visuals.length) {
				return visuals;
			}
		}
		return null;
	}

	// Uses scope.visuals.
	function rbsCatalogProductVisuals() {
		return {
			restrict: 'A',
			link: function(scope, elm, attrs) {
				scope.visualFormats = ['detail', 'detailThumbnail'];
				scope.fullBaseId = attrs.fullBaseId;

				scope.showVisual = function(event) {
					var jNode = jQuery(event.currentTarget);
					var visualId = jNode.attr('data-index');
					jQuery('[id^="' + scope.fullBaseId + '"]').hide();
					jQuery('#' + scope.fullBaseId + visualId).show();
					return false;
				};

				scope.startZoom = function(event) {
					var jNode = jQuery(event.currentTarget);
					var zoomDiv = jQuery('<div class="zoomDiv"><img src="' + jNode.attr('href') + '" alt="" /></div>');
					jNode.after(zoomDiv);

					var image = jNode.children('img');
					var bigImage = zoomDiv.children('img');
					var scaleX = (bigImage.width() / image.width());
					var scaleY = (bigImage.height() / image.height());
					var offset = image.offset();

					jNode.mousemove(function(e) {
						bigImage.css({
							top: Math.max(zoomDiv.height() - bigImage.height(),
								Math.min(0, zoomDiv.height() / 2 - (e.pageY - offset.top) * scaleY)),
							left: Math.max(zoomDiv.width() - bigImage.width(),
								Math.min(0, zoomDiv.width() / 2 - (e.pageX - offset.left) * scaleX))
						});
					});

					jNode.mouseout(function() {
						jQuery('.zoomDiv').remove();
						jNode.unbind('mousemove');
						jNode.unbind('mouseout');
					});

					// Disable the link on the image.
					jNode.click(function() { return false });
				};
			}
		}
	}

	app.directive('rbsCatalogProductVisuals', rbsCatalogProductVisuals);

	function RbsCatalogSimpleProductController(scope, $element, $http, $compile, $rootScope, $window, AjaxAPI) {
		scope.pictograms = null;
		scope.visuals = null;

		scope.productData = {};
		scope.parameters = {};
		scope.productAjaxData = {};
		scope.productAjaxParams = {};
		scope.reviewsAjaxData = {};
		scope.reviewsAjaxParams = {};

		var cacheKey = $element.attr('data-cache-key');
		if (cacheKey) {
			scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
			scope.productData = AjaxAPI.globalVar(cacheKey);

			scope.productAjaxData.webStoreId = scope.parameters.webStoreId;
			scope.productAjaxData.billingAreaId = scope.parameters.billingAreaId;
			scope.productAjaxData.zone = scope.parameters.zone;
			scope.productAjaxParams.visualFormats = scope.parameters.imageFormats;
			scope.productAjaxParams.URLFormats = 'canonical,contextual';
		}

		this.setPictograms = function(productData) {
			scope.pictograms = extractPictogram(productData)
		};

		this.setVisuals = function(productData) {
			scope.visuals = extractVisuals(productData);
		};

		this.setPictograms(scope.productData);

		this.setVisuals(scope.productData);

		scope.addLine = function() {
			addLine(scope, $http, $compile, $rootScope, $window, AjaxAPI);
		};

		scope.showReviews = function() {
			scope.$broadcast('showReviews', $element.attr('data-block-id'));
		};
	}

	RbsCatalogSimpleProductController.$inject = ['$scope', '$element', '$http', '$compile', '$rootScope', '$window', 'RbsChange.AjaxAPI'];
	app.controller('RbsCatalogSimpleProductController', RbsCatalogSimpleProductController);

	function rbsCatalogAddSimpleProductToCart() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogAddSimpleProductToCart.tpl',
			replace: false,
			scope: false,
			link: productDataLink
		}
	}

	app.directive('rbsCatalogAddSimpleProductToCart', rbsCatalogAddSimpleProductToCart);

	function RbsCatalogVariantProductController(scope, $element, $http, $compile, $rootScope, $window, AjaxAPI) {
		scope.pictograms = null;
		scope.visuals = null;

		scope.productData = {};
		scope.parameters = {};
		scope.productAjaxData = {};
		scope.productAjaxParams = {};
		scope.reviewsAjaxData = {};
		scope.reviewsAjaxParams = {};

		var cacheKey = $element.attr('data-cache-key');
		if (cacheKey) {
			scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
			scope.productData = AjaxAPI.globalVar(cacheKey);

			scope.productAjaxData.webStoreId = scope.parameters.webStoreId;
			scope.productAjaxData.billingAreaId = scope.parameters.billingAreaId;
			scope.productAjaxData.zone = scope.parameters.zone;
			scope.productAjaxParams.visualFormats = scope.parameters.imageFormats;
			scope.productAjaxParams.URLFormats = 'canonical,contextual';
		}

		scope.$watch('productData', function(productData) {
			if (productData) {
				scope.pictograms = extractPictogram(productData);
				if (!scope.pictograms && productData.rootProduct) {
					scope.pictograms = extractPictogram(productData.rootProduct);
				}

				scope.visuals = extractVisuals(productData);
				if (!scope.visuals && productData.rootProduct) {
					scope.visuals = extractVisuals(productData.rootProduct);
				}

				if (productData && productData.cart && !productData.cart.quantity) {
					if (productData.cart.minQuantity) {
						productData.cart.quantity = productData.cart.minQuantity;
					}
					else {
						productData.cart.quantity = 1;
					}
				}
			}
		});

		scope.addLine = function() {
			addLine(scope, $http, $compile, $rootScope, $window, AjaxAPI);
		};

		scope.showReviews = function() {
			scope.$broadcast('showReviews', $element.attr('data-block-id'));
		};
	}

	RbsCatalogVariantProductController.$inject = ['$scope', '$element', '$http', '$compile', '$rootScope', '$window',
		'RbsChange.AjaxAPI'];
	app.controller('RbsCatalogVariantProductController', RbsCatalogVariantProductController);

	function rbsCatalogVariantSelector(AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogVariantSelector.tpl',
			replace: false,
			scope: {
				'productData': '=',
				'ajaxData': '=',
				'ajaxParams': '='
			},

			link: function(scope) {
				scope.selectedAxesValues = [];
				scope.axesItems = [];

				scope.loadProductId = null;
				scope.loadedProducts = {};

				scope.originalProductData = null;
				scope.rootProductData = {};

				scope.$watch('productData', function(productData) {
					if (scope.originalProductData === null && angular.isObject(productData)
						&& angular.isObject(productData.common)) {
						scope.productData = productData;
						scope.originalProductData = scope.productData;
						scope.rootProductData = scope.productData.rootProduct || scope.productData;

						scope.$watchCollection('selectedAxesValues', function(definedAxes) {
							var axesDefinition = scope.rootProductData.variants ? scope.rootProductData.variants.axes : [];
							scope.axesItems = [];

							var currentAxesValue = [], axisIndex, values, axisItems;
							for (axisIndex = 0; axisIndex < axesDefinition.length; axisIndex++) {
								if (definedAxes[axisIndex]) {
									values = getChildrenAxesValues(currentAxesValue);
									axisItems = getAxisItems(currentAxesValue, values);
									scope.axesItems.push(axisItems);
									currentAxesValue.push(definedAxes[axisIndex]);
								}
								else if (axisIndex == definedAxes.length) {
									values = getChildrenAxesValues(currentAxesValue);
									axisItems = getAxisItems(currentAxesValue, values);
									scope.axesItems.push(axisItems);

									if (values.length == 1) {
										definedAxes.push(values[0]);
										currentAxesValue.push(definedAxes[axisIndex]);
									}
								}
								else {
									scope.axesItems.push([]);
								}
							}

							if (definedAxes.length) {
								var variant = getVariantByAxesValue(definedAxes);
								if (variant && variant.id != scope.productData.common.id) {
									selectProduct(variant.id);
								}
							}
							else {
								scope.productData = scope.rootProductData;
							}
						});

						if (scope.rootProductData !== scope.productData) {
							for (var i = 0; i < scope.rootProductData.variants.products.length; i++) {
								if (scope.rootProductData.variants.products[i].id == scope.productData.common.id) {
									scope.selectedAxesValues = angular.copy(scope.rootProductData.variants.products[i].axesValues);
									break;
								}
							}
						}
					}
				});

				function compareAxesValues(axesValues, expectedValues, compareType) {
					var i;
					if (!compareType || compareType == '=') {
						if (axesValues.length == expectedValues.length) {
							for (i = 0; i < axesValues.length; i++) {
								if (axesValues[i] != expectedValues[i]) {
									return false;
								}
							}
							return true;
						}
						return false;
					}
					else if (compareType == '<') {
						if (axesValues.length == expectedValues.length - 1) {
							for (i = 0; i < axesValues.length; i++) {
								if (axesValues[i] != expectedValues[i]) {
									return false;
								}
							}
							return true;
						}
						return false;
					}
					else if (compareType == '<<') {
						if (axesValues.length < expectedValues.length) {
							for (i = 0; i < axesValues.length; i++) {
								if (axesValues[i] != expectedValues[i]) {
									return false;
								}
							}
							return true;
						}
						return false;
					}
					return false;
				}

				function getChildrenAxesValues(axesValue) {
					var indexedValues = {}, childrenValues = [], childAxisIndex = axesValue.length,
						variantProducts = scope.rootProductData.variants.products, variantAxesValue, axisValue;
					for (var i = 0; i < variantProducts.length; i++) {
						variantAxesValue = variantProducts[i].axesValues;
						if (compareAxesValues(axesValue, variantAxesValue, '<<')) {
							axisValue = variantAxesValue[childAxisIndex];
							if (!indexedValues.hasOwnProperty(axisValue)) {
								indexedValues[axisValue] = true;
								childrenValues.push(axisValue);
							}
						}
					}
					return childrenValues;
				}

				function getAxisItem(axesValue) {
					var index = axesValue.length - 1,
						axisItem = {
							value: axesValue[index], title: axesValue[index],
							lastAxis: axesValue.length == scope.rootProductData.variants.axes.length
						},
						variantProducts = scope.rootProductData.variants.products, variantAxesValue;
					for (var i = 0; i < variantProducts.length; i++) {
						if (compareAxesValues(axesValue, variantProducts[i].axesValues, '=')) {
							variantAxesValue = variantProducts[i];
							angular.extend(axisItem, variantAxesValue);
						}
					}
					var axis = scope.rootProductData.variants.axes[index];
					if (axis.defaultItems && axis.defaultItems.length) {
						for (i = 0; i < axis.defaultItems.length; i++) {
							if (axis.defaultItems[i].value == axisItem.value) {
								axisItem.title = axis.defaultItems[i].title;
								break;
							}
						}
					}
					return axisItem;
				}

				function getAxisItems(axesValue, values) {
					var axisItems = [];
					for (var i = 0; i < values.length; i++) {
						axesValue.push(values[i]);
						axisItems.push(getAxisItem(axesValue));
						axesValue.pop();
					}
					return axisItems;
				}

				scope.variantChanged = function(axisIndex) {
					scope.selectedAxesValues.length = axisIndex + (scope.selectedAxesValues[axisIndex] !== null ? 1 : 0);
				};

				function selectProduct(productId) {
					if (scope.loadedProducts[productId]) {
						scope.productData = scope.loadedProducts[productId];
					}
					else if (productId == scope.originalProductData.common.id) {
						scope.productData = scope.originalProductData;
					}
					else if (productId == scope.rootProductData.common.id) {
						scope.productData = scope.rootProductData;
					}
					else if (scope.loadProductId != productId) {
						scope.loadProductId = productId;
						var request = AjaxAPI.getData('Rbs/Catalog/Product/' + productId, scope.ajaxData, scope.ajaxParams);
						request.success(function(data) {
							var productData = data.dataSets, loadProductId = productData.common.id;
							productData.rootProduct = scope.rootProductData;
							scope.loadedProducts[loadProductId] = productData;
							if (loadProductId == scope.loadProductId) {
								scope.productData = productData;
							}
						}).error(function(data, status) {
							scope.error = data.message;
							console.log('error', data, status);
						});
					}
				}

				function getVariantByAxesValue(axesValue) {
					var variantProducts = scope.rootProductData.variants.products;
					for (var i = 0; i < variantProducts.length; i++) {
						if (compareAxesValues(axesValue, variantProducts[i].axesValues, '=')) {
							return variantProducts[i]
						}
					}
					return null;
				}
			}
		}
	}

	rbsCatalogVariantSelector.$inject = ['RbsChange.AjaxAPI'];
	app.directive('rbsCatalogVariantSelector', rbsCatalogVariantSelector);

	function rbsCatalogAddVariantProductToCart() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogAddVariantProductToCart.tpl',
			replace: false,
			scope: false,
			link: productDataLink
		}
	}

	app.directive('rbsCatalogAddVariantProductToCart', rbsCatalogAddVariantProductToCart);

	function rbsCatalogAxisOptionClass($parse) {
		return {
			require: 'select',
			link: function(scope, elem, attrs) {
				var optionsSourceStr = attrs.ngOptions.split(' ').pop();
				var getOptionsClass = $parse(attrs['rbsCatalogAxisOptionClass']);

				scope.$watchCollection(optionsSourceStr, function(items) {
					angular.forEach(items, function(item, index) {
						var classes = getOptionsClass(item);
						var option = elem.find('option[value=' + index + ']');
						angular.forEach(classes, function(add, className) {
							if (add) {
								angular.element(option).addClass(className);
							}
							else {
								angular.element(option).removeClass(className);
							}
						});
					});
				});
			}
		};
	}

	app.directive('rbsCatalogAxisOptionClass', ['$parse', rbsCatalogAxisOptionClass]);

	function rbsCatalogProductAvailability() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogProductAvailability.tpl',
			link: function(scope, elm, attrs) {

			}
		}
	}

	app.directive('rbsCatalogProductAvailability', rbsCatalogProductAvailability);

	function RbsCatalogProductSetController(scope, $element, AjaxAPI) {
		scope.pictograms = null;
		scope.visuals = null;

		scope.productData = {};
		scope.parameters = {};
		scope.productAjaxData = {};
		scope.productAjaxParams = {};
		scope.reviewsAjaxData = {};
		scope.reviewsAjaxParams = {};

		var cacheKey = $element.attr('data-cache-key');
		if (cacheKey) {
			scope.parameters = AjaxAPI.getBlockParameters(cacheKey);
			scope.productData = AjaxAPI.globalVar(cacheKey);

			scope.productAjaxData.webStoreId = scope.parameters.webStoreId;
			scope.productAjaxData.billingAreaId = scope.parameters.billingAreaId;
			scope.productAjaxData.zone = scope.parameters.zone;
			scope.productAjaxParams.visualFormats = scope.parameters['imageFormats'];
			scope.productAjaxParams.URLFormats = 'canonical,contextual';
		}

		this.setPictograms = function(productData) {
			scope.pictograms = extractPictogram(productData)
		};

		this.setVisuals = function(productData) {
			scope.visuals = extractVisuals(productData);
		};

		this.setPictograms(scope.productData);

		this.setVisuals(scope.productData);

		scope.showReviews = function() {
			scope.$broadcast('showReviews', $element.attr('data-block-id'));
		};
	}
	RbsCatalogProductSetController.$inject = ['$scope', '$element', 'RbsChange.AjaxAPI'];
	app.controller('RbsCatalogProductSetController', RbsCatalogProductSetController);

	function rbsCatalogAddSetItemProductToCart($http, $compile, $rootScope, $window, AjaxAPI) {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogAddSetItemProductToCart.tpl',
			replace: false,
			scope: {productData: "="},
			link: function(scope, elm, attrs) {
				scope.addLine = function() {
					console.log('addLine');
					addLine(scope, $http, $compile, $rootScope, $window, AjaxAPI);
				};
				productDataLink(scope, elm, attrs);
			}
		}
	}
	rbsCatalogAddSetItemProductToCart.$inject = ['$http', '$compile', '$rootScope', '$window', 'RbsChange.AjaxAPI'];
	app.directive('rbsCatalogAddSetItemProductToCart', rbsCatalogAddSetItemProductToCart);

	function rbsCatalogProductPrice() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogProductPrice.tpl',
			link: function(scope, elm, attrs) {
				scope.displayWithoutTax = (attrs.hasOwnProperty('displayWithoutTax')) ? (attrs.displayWithoutTax == "1") : false;
				scope.displayWithTax = (attrs.hasOwnProperty('displayWithTax')) ? (attrs.displayWithTax == "1") : false;
			}
		}
	}

	app.directive('rbsCatalogProductPrice', rbsCatalogProductPrice);

	app.filter('rbsCatalogGetAttribute', function() {
		function filter(productData, name) {
			if (!angular.isObject(productData) || !angular.isString(name)) {
				return null;
			}
			if (angular.isObject(productData['common']) && angular.isObject(productData['common']['attributes']) &&
				angular.isString(productData['common']['attributes'][name])) {
				var key = productData['common']['attributes'][name];

				if (angular.isObject(productData['attributes']) && angular.isObject(productData['attributes'][key]) &&
					angular.isDefined(productData['attributes'][key]['value'])) {
					return productData['attributes'][key];
				}
			}

			if (angular.isObject(productData['rootProduct'])) {
				return filter(productData['rootProduct'], name);
			}
			return null;
		}

		return filter;
	});

	function rbsCatalogAttributeValue($sce) {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogAttributeValue.tpl',
			replace: false,
			scope: { attribute: '=' },
			link: function(scope) {
				scope.isArrayValue = function(attribute) {
					attribute = attribute || scope.attribute;
					return (attribute && attribute.type == 'DocumentArray');
				};

				scope.isDocument = function(attribute) {
					attribute = attribute || scope.attribute;
					return (attribute && attribute.type == 'Document');
				};

				scope.isLinkValue = function(value) {
					return (value && value.URL && value.URL['canonical']);
				};

				scope.isLink = function(attribute) {
					attribute = attribute || scope.attribute;
					return (scope.isDocument(attribute) && scope.isLinkValue(attribute.value));
				};

				scope.isImageValue = function(value) {
					return (value && value.attribute);
				};

				scope.isImage = function(attribute) {
					attribute = attribute || scope.attribute;
					return (scope.isDocument(attribute) && scope.isImageValue(attribute.value));
				};

				scope.isHtml = function(attribute) {
					attribute = attribute || scope.attribute;
					return (attribute && attribute.type == 'RichText' && angular.isString(attribute.value));
				};

				scope.isDate = function(attribute) {
					attribute = attribute || scope.attribute;
					return (attribute && attribute.type == 'Date');
				};

				scope.isDateTime = function(attribute) {
					attribute = attribute || scope.attribute;
					return (attribute && attribute.type == 'DateTime');
				};
				scope.isString = function(attribute) {
					attribute = attribute || scope.attribute;
					return (attribute && !scope.isDocument(attribute) && !scope.isArrayValue(attribute)
						&& !scope.isHtml(attribute) && !scope.isDate(attribute) && !scope.isDateTime(attribute));
				};

				scope.trustHtml = function(html) {
					return $sce.trustAsHtml(html);
				};
			}
		}
	}

	rbsCatalogAttributeValue.$inject = ['$sce'];
	app.directive('rbsCatalogAttributeValue', rbsCatalogAttributeValue);

	function rbsCatalogProductSpecifications($sce) {
		return {
			restrict: 'A',
			template: '<div></div>',
			scope: { productData: '=' },
			compile: function(elm, attrs) {
				var displayMode = attrs['displayMode'] || 'table';
				var displayDirective = '<div data-rbs-catalog-product-specifications-' + displayMode + '=""></div>';
				elm.html(displayDirective);

				return function(scope, elm, attrs) {
					scope.visibility = attrs.visibility || 'specifications';
					scope.blockId = attrs.blockId || 'product';
					scope.sections = [];
					scope.sectionsAttributes = {};

					scope.trustHtml = function(html) {
						return $sce.trustAsHtml(html);
					};

					function buildAttributes(productData) {
						scope.sections = [];
						scope.sectionsAttributes = {};
						if (!angular.isObject(productData) || !angular.isObject(productData['attributesVisibility'])
							|| !angular.isArray(productData['attributesVisibility'][scope.visibility])) {
							return;
						}
						buildAttributes(productData['rootProduct']);
						var keys = productData['attributesVisibility'][scope.visibility];
						var technicalNamesById = {};
						angular.forEach(productData['common']['attributes'], function(id, technicalName) {
							technicalNamesById[id] = technicalName;
						});
						var attributes = productData['attributes'];
						angular.forEach(keys, function(id) {
							var attribute = attributes[id];
							if (attribute && attribute.value) {
								var section = attribute['section'] || '';
								attribute.id = id;
								if (technicalNamesById['id']) {
									attribute['technicalName'] = technicalNamesById['id'];
								}
								if (!scope.sectionsAttributes[section]) {
									scope.sections.push(section);
									scope.sectionsAttributes[section] = [];
								}
								else {
									for (var i = 0; i < scope.sectionsAttributes[section].length; i++) {
										if (attribute.id == scope.sectionsAttributes[section][i].id) {
											scope.sectionsAttributes[section][i] = attribute;
											return;
										}
									}
								}
								scope.sectionsAttributes[section].push(attribute);
							}
						})
					}

					scope.$watch('productData', function(productData) {
						buildAttributes(productData);
					});
				}
			}
		}
	}

	rbsCatalogProductSpecifications.$inject = ['$sce'];
	app.directive('rbsCatalogProductSpecifications', rbsCatalogProductSpecifications);

	function rbsCatalogProductSpecificationsTable() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogProductSpecificationsTable.tpl',
			link: function(scope) {
				scope.tableRows = [];

				scope.$watch('sectionsAttributes', function(sectionsAttributes) {
					scope.tableRows = [];
					angular.forEach(scope.sections, function(section) {
						scope.tableRows.push({ isSectionTitle: true, section: section });
						angular.forEach(sectionsAttributes[section], function(attribute) {
							scope.tableRows.push(attribute);
						})
					});
				}, true);
			}
		}
	}

	app.directive('rbsCatalogProductSpecificationsTable', rbsCatalogProductSpecificationsTable);

	function rbsCatalogProductSpecificationsAccordion() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogProductSpecificationsAccordion.tpl',
			link: function(scope, elm, attrs) {
			}
		}
	}

	app.directive('rbsCatalogProductSpecificationsAccordion', rbsCatalogProductSpecificationsAccordion);

	function rbsCatalogProductSpecificationsFlat() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogProductSpecificationsFlat.tpl',
			link: function(scope) {
				scope.flatRows = [];
				scope.$watch('sectionsAttributes', function(sectionsAttributes) {
					scope.flatRows = [];
					angular.forEach(scope.sections, function(section) {
						angular.forEach(sectionsAttributes[section], function(attribute) {
							scope.flatRows.push(attribute);
						})
					});
				}, true);
			}
		}
	}

	app.directive('rbsCatalogProductSpecificationsFlat', rbsCatalogProductSpecificationsFlat);

	function rbsCatalogProductSpecificationsTabs() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogProductSpecificationsTabs.tpl',
			link: function(scope, elm, attrs) {
			}
		}
	}

	app.directive('rbsCatalogProductSpecificationsTabs', rbsCatalogProductSpecificationsTabs);

	function rbsCatalogProductInformation($sce) {
		return {
			restrict: 'A',
			template: '<div></div>',
			scope: {
				productData: '=',
				reviewsAjaxData: '=',
				reviewsAjaxParams: '='
			},
			compile: function(elm, attrs) {
				var blockId = attrs.blockId || 'product';
				var displayMode = attrs['displayMode'] || 'tabs';
				var specificationsDisplayMode = attrs['specificationsDisplayMode'];
				var displayDirective = '<div data-rbs-catalog-product-information-' + displayMode + '="">';
				if (specificationsDisplayMode) {
					displayDirective += '<div data-rbs-catalog-product-specifications="" data-product-data="productData"' +
					' data-visibility="specifications" data-display-mode="' + specificationsDisplayMode + '"' +
					' data-block-id="' + blockId + '"></div>';
				}
				displayDirective += '</div>';
				elm.html(displayDirective);

				return function(scope, elm, attrs) {
					function getNonEmptyAttributes(visibility) {
						var attributes = [];
						var attributeIds = scope.productData['attributesVisibility'][visibility];
						if (angular.isArray(attributeIds)) {
							for (var i = 0; i < attributeIds.length; i++) {
								var attribute = scope.productData.attributes[attributeIds[i]];
								if (attribute.value) {
									attributes.push(attribute);
								}
								else if (angular.isObject(scope.productData['rootProduct'])) {
									attribute = scope.productData['rootProduct'].attributes[attributeIds[i]];
									if (attribute.value) {
										attributes.push(attribute);
									}
								}
							}
						}
						return attributes;
					}

					scope.blockId = attrs.blockId || 'product';
					scope.attributesDisplayMode = attrs.attributesDisplayMode || 'table';

					scope.handleReviews = attrs.handleReviews == 1;
					scope.reviewsPerPage = parseInt(attrs.reviewsPerPage) || 10;
					scope.ratingScale = parseInt(attrs.ratingScale) || 5;
					scope.handleReviewVotes = attrs.handleReviewVotes == 1;

					scope.specificInformation = getNonEmptyAttributes('information');
					scope.specifications = getNonEmptyAttributes('specifications');

					scope.trustHtml = function(html) {
						return $sce.trustAsHtml(html);
					};
				}
			}
		}
	}

	rbsCatalogProductInformation.$inject = ['$sce'];
	app.directive('rbsCatalogProductInformation', rbsCatalogProductInformation);

	function rbsCatalogProductInformationAccordion() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogProductInformationAccordion.tpl',
			transclude: true,
			link: function(scope, elm) {
				scope.baseId = 'block-' + scope.blockId + '-information-accordion';
				var selector = '#' + scope.baseId + '-reviews';

				scope.$on('showReviews', function (event, blockId) {
					if (blockId == scope.blockId) {
						elm.find(selector).collapse('show');
						jQuery('html, body').animate({ scrollTop: elm.find('a[href="' + selector + '"]').offset().top - 20 }, 1000);
					}
				});
			}
		}
	}

	app.directive('rbsCatalogProductInformationAccordion', rbsCatalogProductInformationAccordion);

	function rbsCatalogProductInformationFlat() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogProductInformationFlat.tpl',
			transclude: true,
			link: function(scope, elm) {
				scope.baseId = 'block-' + scope.blockId + '-information-flat';
				var selector = '#' + scope.baseId + '-reviews';

				scope.$on('showReviews', function (event, blockId) {
					if (blockId == scope.blockId) {
						jQuery('html, body').animate({ scrollTop: elm.find(selector).offset().top - 20 }, 1000);
					}
				});
			}
		}
	}

	app.directive('rbsCatalogProductInformationFlat', rbsCatalogProductInformationFlat);

	function rbsCatalogProductInformationTabs() {
		return {
			restrict: 'A',
			templateUrl: '/rbsCatalogProductInformationTabs.tpl',
			transclude: true,
			link: function(scope, elm) {
				scope.baseId = 'block-' + scope.blockId + '-information-tab';
				var selector = 'a[href="#' + scope.baseId + '-reviews"]';

				scope.$on('showReviews', function (event, blockId) {
					if (blockId == scope.blockId) {
						elm.find(selector).tab('show');
						jQuery('html, body').animate({ scrollTop: elm.find(selector).offset().top - 20 }, 1000);
					}
				});
			}
		}
	}

	app.directive('rbsCatalogProductInformationTabs', rbsCatalogProductInformationTabs);
})(jQuery);