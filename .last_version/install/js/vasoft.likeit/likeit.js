Likeit = {
    list: [],
    class: 'vs-likeit',
    classActive: 'vs-likeit-active',
    classCnt: 'vs-likeit-cnt',
    classAction: 'vs-likeit-action',
    onLike: function (data) {
    },
    onClick: function () {
    },
    init: function () {
        BX.Vasoft.Likeit.init();
    },
    onList: function (data) {
    }
};
BX.ready(function () {
    BX.Vasoft.Likeit.init();
});
;(function () {
    'use strict';
    BX.namespace('Vasoft.Likeit');
    BX.Vasoft.Likeit.state = {
        list: [],
        class: 'vs-likeit',
        classActive: 'vs-likeit-active',
        classCnt: 'vs-likeit-cnt',
        classAction: 'vs-likeit-action',
    };
    BX.Vasoft.Likeit.init = function () {
        let state = BX.Vasoft.Likeit.state;
        let elements = document.querySelectorAll('.' + state.class), i, ids = [];
        if (window.hasOwnProperty('vas_likeit_classactive') && window['vas_likeit_classactive'] !== '') {
            state.classActive = window['vas_likeit_classactive'];
        }
        if (window.hasOwnProperty('vas_likeit_classcnt') && window['vas_likeit_classcnt'] !== '') {
            state.classCnt = window['vas_likeit_classcnt'];
        }
        state.list = [];
        for (i = 0; i < elements.length; ++i) {
            let el = BX(elements[i]), id = el.getAttribute('dataid') || 0;
            BX.unbind(el, 'click', BX.Vasoft.Likeit.onClick);
            if (id > 0) {
                ids.push(id);
                if (BX.hasClass(el, state.classAction)) {
                    BX.bind(el, 'click', BX.Vasoft.Likeit.onClick);
                }
                if (id in state.list) {
                    state.list[id].push(el);
                } else {
                    state.list[id] = [el];
                }
            }
        }
        if (ids.length > 0) {
            BX.ajax.runAction('vasoft:likeit.Likes.list', {data: {ids: ids}}).then(BX.Vasoft.Likeit.onList);
        }
    };
    BX.Vasoft.Likeit.onList = function (response) {
        if (response.hasOwnProperty('data') && response.data.hasOwnProperty('ITEMS')) {
            let state = BX.Vasoft.Likeit.state, i, j, items = response.data.ITEMS;
            for (i = 0; i < response.data.ITEMS.length; ++i) {
                if (items[i].ID in state.list) {
                    for (j = 0; j < state.list[items[i].ID].length; ++j) {
                        let el = state.list[items[i].ID][j];
                        if (items[i].CHECKED > 0) {
                            BX.addClass(el, state.classActive);
                        }
                        let cnt = BX.findChildByClassName(el, state.classCnt);
                        if (!!cnt) {
                            cnt.innerText = items[i].CNT;
                        }
                    }
                }
            }
        }
    };
    BX.Vasoft.Likeit.onClick = function () {
        let el = BX(this), id = el.getAttribute('dataid') || 0;
        if (id > 0) {
            BX.ajax.runAction('vasoft:likeit.Likes.like', {data: {id: id}}).then(BX.Vasoft.Likeit.onLike);
        }
        return false;
    };
    BX.Vasoft.Likeit.onLike = function (response) {
        if (response.hasOwnProperty('data') && response.data.hasOwnProperty('result')) {
            let state = BX.Vasoft.Likeit.state, j, elements = state.list[response.data.id],
                result = parseInt(response.data.result);
            for (j = 0; j < elements.length; ++j) {
                let el = elements[j], cnt = BX.findChildByClassName(el, state.classCnt);
                if (result === 1) {
                    BX.addClass(el, state.classActive);
                    if (!!cnt) {
                        cnt.innerText = parseInt(cnt.innerText) + 1;
                    }
                } else {
                    BX.removeClass(el, state.classActive);
                    if (!!cnt) {
                        cnt.innerText = parseInt(cnt.innerText) - 1;
                    }
                }
            }
        }
    };
})();