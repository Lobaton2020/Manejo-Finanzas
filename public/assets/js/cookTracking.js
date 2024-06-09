
const destroyCalendar = () => {
    if ($('#calendar').children().length > 0) {
        $('#calendar').fullCalendar('destroy');
        $('#calendar').html('');
    }
    $.CalendarPage.init()
}

const queryDataEvents = async (year, month) => {
    let result = await fetch(`${URL_PROJECT}cookTracking/getMarkedDaysPerAnioMonth/${year}/${month}`);
    result = await result.json();
    return result.data;
}
const queryTotalTodo = async () => {
    let result = await fetch(`${URL_PROJECT}cookTracking/getTotalTodo`);
    result = await result.json();
    return result.data.total_todo;
}
const storeDataEvents = async (payload) => {
    let result = await fetch(`${URL_PROJECT}cookTracking/store`, options(payload));
    result = await result.json();
    return result.data;
}

function updateURLWithDate(action) {
    const urlParams = new URLSearchParams(window.location.search);
    let year = urlParams.get('year');
    let month = urlParams.get('month');

    if (!year || !month) {
        year = moment().year();
        month = moment().month() + 1;
    }

    let date = moment(`${year}-${month}-01`, "YYYY-MM-DD");

    if (action === 'add') {
        date.add(1, 'months');
    } else if (action === 'subtract') {
        date.subtract(1, 'months');
    } else {
        const newUrl = window.location.pathname;
        window.history.pushState({ path: newUrl }, '', newUrl);
        location.reload();
        return;
    }

    urlParams.set('year', date.year());
    urlParams.set('month', date.month() + 1);

    const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
    window.history.pushState({ path: newUrl }, '', newUrl);

    console.log("Nueva URL: " + newUrl);
}

const loadCalendar = async () => {
    let date = moment();
    const queryParams = new URLSearchParams(window.location.search);
    if (queryParams.get("year") && queryParams.get("month")) {
        date.year(parseInt(queryParams.get("year")))
        date.month(parseInt(queryParams.get("month")) + 1)
    }
    let events = await queryDataEvents(date.format("YYYY"), date.format("MM")) ?? []
    events = events.map(({ dia, descripcion }) => {
        const _date = new Date(date.format("YYYY-MM-" + dia));
        _date.setUTCHours(_date.getUTCHours() + 5)
        return {
            title: descripcion ?? "Grays Cooked",
            start: _date
        }
    })

    const baseValue = parseInt($("#valor-unitario").val().replace(".", ''));
    queryTotalTodo()
        .then((total_todo) => {
            $("#total-todo").val((parseInt(total_todo) * baseValue).toLocaleString('de-DE'))
        })
    $("#valor-total").val((events.length * baseValue).toLocaleString('de-DE'))
    $('#calendar').fullCalendar({
        defaultDate: new Date(date.format()),
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,basicWeek,basicDay'
        },
        editable: false,
        eventLimit: true, // allow "more" link when too many events
        droppable: true, // this allows things to be dropped onto the calendar !!!
        drop: function (date, allDay) { // this function is called when something is dropped
            var originalEventObject = $(this).data('eventObject');
            storeDataEvents({ date: date.format("YYYY-MM-DD"), descripcion: originalEventObject.title })
                .then(async () => {
                    destroyCalendar()
                    await loadCalendar()
                })
                .catch(error => {
                    console.error(error)
                    alert("Error al guardar")
                    queryDataEvents(date.format("YYYY"), date.format("MM"))
                })
            var copiedEventObject = $.extend({}, originalEventObject);
            copiedEventObject.start = date;
            copiedEventObject.allDay = allDay;
            $('#calendar').fullCalendar('renderEvent', copiedEventObject, true);

        },

        events
    });


}


var CalendarPage = function () { };
CalendarPage.prototype.init = async function () {

    if ($.isFunction($.fn.fullCalendar)) {
        $('#external-events .fc-event').each(function () {
            var eventObject = {
                title: $.trim($(this).text()) // use the element's text as the event title
            };
            $(this).data('eventObject', eventObject);
            $(this).draggable({
                zIndex: 999,
                revert: true, // will cause the event to go back to its
                revertDuration: 0 //  original position after the drag
            });

        });

        await loadCalendar();
        $('.fc-prev-button').click(function () {
            updateURLWithDate('subtract')
            destroyCalendar()
            loadCalendar()
        });

        $('.fc-next-button').click(function () {
            updateURLWithDate('add')
            destroyCalendar()
            loadCalendar()
        });
        $('#calendar > div.fc-toolbar > div.fc-left > button').click(function () {
            updateURLWithDate()
            destroyCalendar()
            loadCalendar()
        });
        $("#add_event_form").on('submit', function (ev) {
            ev.preventDefault();
            var $event = $(this).find('.new-event-form'),
                event_name = $event.val();
            if (event_name.length >= 3) {
                var newid = "new" + "" + Math.random().toString(36).substring(7);
                $("#external-events").append(
                    '<div id="' + newid + '" class="fc-event">' + event_name + '</div>'
                );
                var eventObject = {
                    title: $.trim($("#" + newid).text())
                };
                $("#" + newid).data('eventObject', eventObject);
                $("#" + newid).draggable({
                    revert: true,
                    revertDuration: 0,
                    zIndex: 999
                });
                $event.val('').focus();
            } else {
                $event.focus();
            }
        });

    }
    else {
        alert("Calendar plugin is not installed");
    }
}
//init
$.CalendarPage = new CalendarPage, $.CalendarPage.Constructor = CalendarPage

document.addEventListener("DOMContentLoaded", async () => {
    try {
        await $.CalendarPage.init()
    } catch (error) {
        console.log("ERROR CALENDARIO", error)
    }

})

