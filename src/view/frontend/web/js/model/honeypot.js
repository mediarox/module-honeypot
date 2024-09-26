define([
    'ko',
    'uiComponent',
    'underscore',
], function (ko, Component, _) {
    'use strict';

    return Component.extend({
        defaults: {
            forms: [],
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
            this.observerMutations(this.forms, this.fieldName, this.fieldClass);
        },


        observerMutations: function (forms, fieldName, fieldClass) {
            let observer = new MutationObserver(mutations => {

                for(let mutation of mutations) {
                    // examine new nodes, is there anything to highlight?

                    for(let node of mutation.addedNodes) {
                        // we track only elements, skip other nodes (e.g. text nodes)
                        if (!(node instanceof HTMLElement)) continue;

                        // check the inserted element for being a form
                        let forms = node.querySelectorAll('form');
                        if (forms.length > 0) {
                            _.forEach(forms, form => {
                                this.addHoneypot(form);
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
        }
    })


});
