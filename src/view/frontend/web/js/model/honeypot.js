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
            let domFroms = document.querySelectorAll(this.forms);
            _.each(domFroms, function (form) {
                this.addHoneypot(form);
                this.addTimestamp(form);
            }, this);
            this.observerMutations();
        },


        observerMutations: function () {
            let observer = new MutationObserver(mutations => {
                let that = this;
                for(let mutation of mutations) {
                    for(let node of mutation.addedNodes) {
                        // we track only elements, skip other nodes (e.g. text nodes)
                        if (!(node instanceof HTMLElement)) continue;

                        let domFroms = this.getValidForms(node);
                        if (domFroms.length > 0) {
                            _.forEach(domFroms, form => {
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

        getValidForms: function (node) {
            let domFroms = [];
            // check the inserted element for containing forms
            if (node.tagName === 'FORM') {
                let nodeClasses = node.className;
                nodeClasses = nodeClasses.split(' ');
                nodeClasses = '.' + nodeClasses.join('.');
                let nodeId = '#' + node.id;
                let validNode = this.forms.includes(nodeClasses) || this.forms.includes(nodeId);
                if (validNode) {
                    domFroms.push(node);
                }
            } else {
                domFroms = node.querySelectorAll(this.forms);
            }
            return domFroms;
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
