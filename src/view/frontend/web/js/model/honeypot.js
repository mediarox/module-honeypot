define([
    'ko',
    'uiComponent',
    'underscore',
], function (ko, Component, _) {
    'use strict';

    return Component.extend({
        defaults: {
            fieldName: '',
            fieldClass: ''
        },

        initialize: function () {
            this._super();
            this.extendForms();
        },

        extendForms: function () {
            let domFroms = document.getElementsByTagName('form');
            _.each(domFroms, function (form) {
                this.addHoneypot(form);
            }, this);
            this.observerMutations(this.fieldName, this.fieldClass);
        },


        observerMutations: function (fieldName, fieldClass) {
            let observer = new MutationObserver(mutations => {

                for(let mutation of mutations) {
                    for(let node of mutation.addedNodes) {
                        // we track only elements, skip other nodes (e.g. text nodes)
                        if (!(node instanceof HTMLElement)) continue;

                        let forms = [];
                        // check the inserted element for containing forms
                        if (node.tagName === 'FORM') {
                            forms.push(node);
                        } else {
                            forms = node.querySelectorAll('form');
                        }
                        if (forms.length > 0) {
                            _.forEach(forms, form => {
                                this.addHoneypot(form);
                                this.addTimestamp(form);
                            })
                        }
                    }
                }

            });
            let body = document.getElementsByTagName('body')[0];
            observer.observe(body, {attributes: false, childList: true, characterData: false, subtree:true});
        },

        addHoneypot: function (form) {
            var element = document.createElement('input');
            element.type = 'text';
            element.name = this.fieldName;
            element.className = this.fieldClass;
            element.style.cssText = 'display: none';
            form.appendChild(element);
        },

        addTimestamp: function (form) {
            var element = document.createElement('input');
            element.type = 'text';
            element.name = 'timestamp';
            element.className = this.fieldClass;
            element.style.cssText = 'display: none';
            element.value = Date.now();
            form.appendChild(element);
        }
    })


});
