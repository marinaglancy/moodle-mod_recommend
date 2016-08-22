// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Edit items in recommend module
 *
 * @module     mod_recommend/edit
 * @package    mod_recommend
 * @copyright  2016 Marina Glancy
 */
define(['jquery', 'core/ajax', 'core/str', 'core/config', 'core/notification', 'core/log'],
function($, ajax, str, config, notification, l) {
    return {

        setup: function(types) {
            var buildlink = function(action, id, content) {
                var url = window.location.href.match(/(^[^#]*)/)[0];
                var newurl = url + "&action=" + action + "&questionid=" + id + '&sesskey=' + config.sesskey;
                return '<a href="' + newurl + '" data-action="' + action + '" data-questionid="' + id + '">' + content + '</a> ';
            };

            var inititem = function(item, isfirst, islast) {
                var myRegexp = /^(fitem|fgroup)_id_question(\d+)(_label)?$/g;
                var match = myRegexp.exec(item.attr('id'));
                var id = parseInt(match[2]);
                var editcontrols = $('<div class="editcontrols" data-questionid="' + id + '"></div>');
                if (id) {
                    if (!isfirst) {
                        editcontrols.append(buildlink('moveup', id, 'up'));
                    }
                    if (!islast) {
                        editcontrols.append(buildlink('movedown', id, 'down'));
                    }
                    editcontrols.append(buildlink('add', id, 'insert'));
                    editcontrols.append(buildlink('edit', id, 'edit'));
                    editcontrols.append(buildlink('duplicate', id, 'duplicate'));
                    editcontrols.append(buildlink('delete', id, 'delete'));
                } else {
                    editcontrols.append(buildlink('add', id, 'add question'));
                }
                item.append(editcontrols);
            };

            var selector = 'form.mform.mod-recommend-recommendation.editing';
            var fitem = $(selector + ' .fitem');
            var length = fitem.length;
            fitem.each(
                function(index) {
                    inititem($(this), index === 0, index === length - 1);
                });
            var dummyitem = $('<div class="fitem dummyitem" id="fitem_id_question0">&nbsp;<br>&nbsp;<br></div>');
            $(selector).append(dummyitem);
            inititem(dummyitem);

            $('body').on('click', selector + ' .editcontrols a[data-action=add]', function(e) {
                e.preventDefault();
                var href = $(this).attr('href');
                str.get_strings([
                        {key : 'addquestion', component : 'mod_recommend'},
                        {key : 'selectquestiontype', component : 'mod_recommend'},
                        {key : 'cancel'}
                    ]).done(function(s) {
                        var el = $('<div><div id="mod_recommend_type_selector">'
                            + '<p class="selecttype"></p>'
                            + '<ul class="typeslist"></ul>'
                            + '<p><input type="button" id="type_selector_cancel"/></p>'
                            + '</div></div>');
                        el.find('.selecttype').html(s[1]);
                        el.find('#type_selector_cancel').attr('value', s[2]);
                        for (var type in types) {
                            var link = $('<li><a></a></li>');
                            link.find('a').attr('href', href + '&type=' + type).html(types[type]);
                            el.find('.typeslist').append(link);
                        }
                        var panel = new M.core.dialogue ({
                            draggable: true,
                            modal: true,
                            closeButton: true,
                            headerContent: s[0],
                            bodyContent: el.html()
                        });
                        panel.show();
                        $('#mod_recommend_type_selector #type_selector_cancel').on('click', function() {
                            l.debug('click on cancel');
                            panel.destroy();
                        });
                    }
                );

            });

            $('body').on('click', selector + ' .editcontrols a[data-action=delete]', function(e) {
                e.preventDefault();
                var href = $(this).attr('href');
                str.get_strings([
                        {key : 'delete'},
                        {key : 'suredeletequestion', component : 'mod_recommend' },
                        {key : 'yes'},
                        {key : 'no'},
                    ]).done(function(s) {
                        notification.confirm(s[0], s[1], s[2], s[3], function() {
                            window.location.href = href;
                        });
                    }
                );
            });
        }
    };
});
