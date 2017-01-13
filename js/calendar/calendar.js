
(function(root, factory) {
    if (typeof define === 'function' && define.amd) {
        define('calendar', ['jquery'], factory);
    } else if (typeof exports === 'object') {
        module.exports = factory(require('jquery'));
    } else {
        factory(root.jQuery);
    }
}(this, function($) {

    // default config

    var defaults = {

            // 宽度
            width: 280,
            // 高度, 不包含头部，头部固定高度
            height: 280,

            zIndex: 1,

            // 自定义类，用于重写样式
            customClass: '',

			curdate: '',

            // 默认日期为当前日期
            date: new Date(),
            format: 'yyyy/mm/dd',

            // 一周的第一天
            // 0表示周日，依次类推
            startWeek: 0,

            // 星期格式
            weekArray: ['日', '一', '二', '三', '四', '五', '六'],

            // 日期关联数据 [{ date: string, value: object }, ... ]
            // 日期格式与 format 一致
            // 如 [ {date: '2015/11/23', value: '面试'} ]
            data: null,

            // 展示关联数据
            // 格式化参数：{m}视图，{d}日期，{v}value
            // 设置 false 表示不显示
            label: '{d}\n{v}',

            // view: 视图
            // date: 不同视图返回不同的值
            // value: 日期关联数据
            onSelected: function(view, date, value) {
                // body...
            },

            // 参数同上
            onMouseenter: $.noop,

            onClose: $.noop
        },

    // static variable
        ITEM_DAY = 'data-calendar-day',
		ITEM_OLD_DAY = 'data-calendar-oldday',

        DISABLED = 'disabled',
        MARK_DATA = 'markData',

        VIEW_CLASS = 'calendar-d',

        OLD_DAY_CLASS = 'old',
        NEW_DAY_CLASS = 'new',
        NEXT_DAY_CLASS = 'next',
		HOVER_DAY_CLASS = 'hover',
        TODAY_CLASS = 'new',
        SELECT_CLASS = 'selected',

        ITEM_STYLE = 'style="width:{w}px;height:{h}px;line-height:{h}px"',
        WEEK_ITEM_TPL = '<li ' + ITEM_STYLE + '>{wk}</li>',
        DAY_ITEM_TPL = '<li ' + ITEM_STYLE + ' class="{class}" {action}>{value}</li>',

        TEMPLATE = [
            '<div class="calendar-inner">',
            '<div class="calendar-views">',
            '<div class="view view-date">',
            '<div class="calendar-ct">',
            '<ol class="week">{week}</ol>',
            '<ul class="date-items"></ul>',
            '</div>',
            '</div>',
            '</div>',
            '</div>'
        ],
        OS = Object.prototype.toString;

    // utils

    function isDate(obj) {
        return OS.call(obj) === '[object Date]';
    }

    function isString(obj) {
        return OS.call(obj) === '[object String]';
    }


    function getClass(el) {
        return el.getAttribute('class') || el.getAttribute('className');
    }

    // extension methods
    String.prototype.repeat = function(data) {
        return this.replace(/\{\w+\}/g, function(str) {
            var prop = str.replace(/\{|\}/g, '');
            return data[prop] || '';
        });
    }

    String.prototype.toDate = function() {
        var dt = new Date(),
            dot = this.replace(/\d/g, '').charAt(0),
            arr = this.split(dot);

        dt.setFullYear(arr[0]);
        dt.setMonth(arr[1] - 1);
        dt.setDate(arr[2]);
        return dt;
    }

    Date.prototype.format = function(exp) {
        var y = this.getFullYear(),
            m = this.getMonth() + 1,
            d = this.getDate();

        return exp.replace('yyyy', y).replace('mm', m).replace('dd', d);
    }

    Date.prototype.isSame = function(y, m, d) {
        if (isDate(y))
		{
            var dt = y;
            y = dt.getFullYear();
            m = dt.getMonth() + 1;
            d = dt.getDate();
        }
        return this.getFullYear() === y && this.getMonth() + 1 === m && this.getDate() === d;
    }

    //判断闰年
    Date.isLeap = function(y) {
        return (y % 100 !== 0 && y % 4 === 0) || (y % 400 === 0);
    }

    Date.getDaysNum = function(y, m) {
        var num = 31;

        switch (m)
        {
            case 2:
                num = this.isLeap(y) ? 29 : 28;
                break;
            case 4:
            case 6:
            case 9:
            case 11:
                num = 30;
                break;
        }
        return num;
    }

    Date.getSiblingsMonth = function(y, m, n) {
        var d = new Date(y, m - 1);
        d.setMonth(m - 1 + n);
        return {
            y: d.getFullYear(),
            m: d.getMonth() + 1
        };
    }

    Date.getPrevMonth = function(y, m, n) {
        return this.getSiblingsMonth(y, m, 0 - (n || 1));
    }

    Date.getNextMonth = function(y, m, n) {
        return this.getSiblingsMonth(y, m, n || 1);
    }

    // Calendar class
    function Calendar(element, options) {
        this.$element = $(element);
        this.options = $.extend({}, $.fn.calendar.defaults, options);
        this.$element.addClass('calendar ' + this.options.customClass);
        this.width = this.options.width;
        this.height = this.options.height;
        this.date = this.options.date;
        this.data = this.options.data;
		this.curdate = this.options.curdate;
        this.init();
    }

    Calendar.prototype = {
        constructor: Calendar,

        getDayData: function(day) {
            var ret, data = this.data;

            if (data)
            {
                for (var i = 0, len = data.length; i < len; i++)
                {
                    var item = data[i];
                    if (day.isSame(item.date.toDate()))
                    {
                        ret = item.value;
                    }
                }
            }

            return ret;
        },
        getDayItem: function(y, m, d, f) {
            var dt = this.date,
                idt = new Date(y, m - 1, d),
                data = {
                    w: this.width / 7,
                    h: this.height / 7,
                    value: d
                },
                markData,
                $item;

            if (f === 1)
			{
                data['class'] = OLD_DAY_CLASS;
				data.action = ITEM_OLD_DAY;
            }  
			else if (f === 2)
			{
                data['class'] = NEW_DAY_CLASS;
				data.action = ITEM_DAY;
            }  
			else if (f === 3)
			{
                data['class'] = NEXT_DAY_CLASS;
				data.action = ITEM_DAY;
            }
			else 
			{
                data['class'] = '';
				data.action = ITEM_DAY;
            }

            if (dt.isSame(y, m, d))
			{
                data['class'] = TODAY_CLASS;
				data.action = ITEM_OLD_DAY;
				//console.log(y+'-'+m+'-'+d);
            }

            
            markData = this.getDayData(idt);
            if (markData) 
			{
				data['class'] = OLD_DAY_CLASS;
				data.action = ITEM_OLD_DAY;
			}

			var curdate = this.curdate;
			if (curdate != null && curdate != "")
			{
				var curyear = curdate.getFullYear();
				var curmonth = curdate.getMonth()+1;
				var curday = curdate.getDate();
				console.log(curyear+'-'+curmonth+'-'+curday);
				if (curdate.isSame(y, m, d))
				{
					data['class'] = HOVER_DAY_CLASS;
				}
			}
            $item = $(DAY_ITEM_TPL.repeat(data));



            if (markData) 
			{
                var fullhtml = "<div style='height:2.0em;'><div style='height:0.5em;margin-top:-0.5em;'>"+d+"</div><div style='padding:0px;margin-top:0.8em;height:0.5em;font-size:0.5em;'>"+markData+"</div></div>";
                $item.data(MARK_DATA, markData);
                $item.html(fullhtml);
            }

            if (dt.isSame(y, m, d))
            {
                var fullhtml = "今天";
                $item.html(fullhtml);
            }
			if (d===1)
			{
                var fullhtml2 = m+"月";

				if (markData) 
				{
					fullhtml = "<div style='height:2.0em;'><div style='height:0.5em;margin-top:-0.5em;'>"+fullhtml2+"</div><div style='padding:0px;margin-top:0.8em;height:0.5em;font-size:0.5em;'>"+markData+"</div></div>";
					$item.data(MARK_DATA, markData);
					$item.html(fullhtml);
				}
				else
				{
					$item.html(fullhtml2);
				}
			}
            return $item;
        },
        getDaysHtml: function(y, m) {
            var year, month, firstWeek, daysNum, prevM, prevDiff,
                dt = this.date,
                $days = $('<ol class="days"></ol>');

            if (isDate(y)) {
                year = y.getFullYear();
                month = y.getMonth() + 1;
            } else {
                year = Number(y);
                month = Number(m);
            }

            firstWeek = new Date(year, month - 1, 1).getDay() || 7;
            prevDiff = firstWeek - this.options.startWeek;
            daysNum = Date.getDaysNum(year, month);
            prevM = Date.getPrevMonth(year, month);
            prevDaysNum = Date.getDaysNum(year, prevM.m);
            nextM = Date.getNextMonth(year, month);
            // month flag
            var PREV_FLAG = 1,
                CURR_FLAG = 2,
                NEXT_FLAG = 3,
                count = 0;

			//console.log('year='+year+',month='+month);

            var weekday = new Date().getDay(); //周几 周日是0
            var monthday = new Date().getDate(); //几号

            //console.log('weekday='+weekday+',monthday='+monthday+',firstWeek='+firstWeek+',prevDiff='+prevDiff+',daysNum='+daysNum+',prevM.m='+prevM.m+',prevDaysNum='+prevDaysNum+',nextM.y='+nextM.y+',nextM.m='+nextM.m);

			var predays = weekday-(monthday-1);
			if (weekday == 0)
			{
				predays = 0;
			}
			prevDiff = predays;

            for (var p = prevDaysNum - prevDiff + 1; p <= prevDaysNum; p++, count++)
            {
                $days.append(this.getDayItem(prevM.y, prevM.m, p, PREV_FLAG));
            }

			if (monthday >= 1)
			{
				var startday = monthday - weekday;
				if (startday < 1)
				{
					startday = 1;
				}
				var endday = monthday;
				for (var c = startday; c < endday; c++, count++)
				{
					$days.append(this.getDayItem(year, month, c, PREV_FLAG));
				}
			}

            for (var c = monthday; c <= daysNum && count<=35; c++, count++)
            {
                $days.append(this.getDayItem(year, month, c, CURR_FLAG));
            }

            for (var n = 1, nl = 35 - count; n <= nl; n++)
            {
                $days.append(this.getDayItem(nextM.y, nextM.m, n, NEXT_FLAG));
            }

            return $('<li></li>').width(this.options.width).append($days);
        },
        getWeekHtml: function() {
            var week = [],
                weekArray = this.options.weekArray,
                start = this.options.startWeek,
                len = weekArray.length,
                w = this.width / 7,
                h = this.height / 7;

            for (var i = start; i < len; i++)
            {
                week.push(WEEK_ITEM_TPL.repeat({
                    w: w,
                    h: h,
                    wk: weekArray[i]
                }));
            }

            for (var j = 0; j < start; j++)
            {
                week.push(WEEK_ITEM_TPL.repeat({
                    w: w,
                    h: h,
                    wk: weekArray[j]
                }));
            }

            return week.join('');
        },
        fillStatic: function() {
            var staticData = {
                week: this.getWeekHtml()
            };

            this.$element.html(TEMPLATE.join('').repeat(staticData));
        },
        fillDateItems: function(y, m) {
            this.$dateItems.html('');

            var $item = this.getDaysHtml(y, m);
            this.$dateItems.append($item);
        },
        render: function() {
            this.$week = this.$element.find('.week');
            this.$dateItems = this.$element.find('.date-items');
            this.$label = this.$element.find('.calendar-label');

            var y = this.date.getFullYear(),
                m = this.date.getMonth() + 1;

            this.fillDateItems(y, m);
        },
        setView: function() {
            this.$element.removeClass(VIEW_CLASS).addClass(VIEW_CLASS);
        },
        getDisDateValue: function() {
            var y = this.date.getFullYear(),
                m = this.date.getMonth() + 1;
            return [y, m];
        },
        selectedDay: function(d, cls) {
            var arr = this.getDisDateValue(),
                y = arr[0],
                m = arr[1],
                toggleClass = function() {
                    this.$dateItems.children(':eq(0)')
                        .find('[' + ITEM_DAY + ']:not(.' + NEW_DAY_CLASS + ', .' + OLD_DAY_CLASS + ')')
                        .removeClass(SELECT_CLASS)
                        .filter(function(index) {
                            return parseInt(this.innerHTML) === d;
                        }).addClass(SELECT_CLASS);
                };
			
			if (cls === NEXT_DAY_CLASS)
			{
				nextM = Date.getNextMonth(y, m);
				y = nextM.y;
				m = nextM.m;
			}
			if (m < 10)
			{
				m = '0'+m;
			}
            return y+"-"+m+"-"+d;
        },
        event: function() {
            var _this = this;

            // selected
            _this.$element.on('click', '[' + ITEM_DAY + ']', function() {
				var days = this.innerHTML;
				var d = parseInt(days); //wluotest
				if (days.indexOf("月") >= 0)
				{
					d = 1;
				}

                var cls = getClass(this);
				if (cls == OLD_DAY_CLASS)
				{
					return false;
				}

                $(this).siblings().removeClass(HOVER_DAY_CLASS);
				$(this).addClass(HOVER_DAY_CLASS);


                var day = _this.selectedDay(d, cls);

                _this.options.onSelected.call(this, 'date', day, $(this).data(MARK_DATA));
            });
        },
        resize: function() {
            var w = this.width,
                h = this.height;

            this.$element.width(w).height(h)
                .find('.calendar-inner, .view')
                .css('width', w + 'px');

            this.$element.find('.calendar-ct').width(w).height(h);
        },
        init: function() {
            this.fillStatic();
            this.resize();
            this.render();
            this.setView();
            this.event();
        },
        methods: function(name, args) {
            if (OS.call(this[name]) === '[object Function]') {
                return this[name].apply(this, args);
            }
        }
    };

    $.fn.calendar = function(options) {
        var calendar = this.data('calendar'),
            fn,
            args = [].slice.call(arguments);

        if (!calendar) {
            return this.each(function() {
                return $(this).data('calendar', new Calendar(this, options));
            });
        }
        if (isString(options)) {
            fn = options;
            args.shift();
            return calendar.methods(fn, args);
        }

        return this;
    }

    $.fn.calendar.defaults = defaults;

}));
