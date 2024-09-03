const notifierCalendar = new EventTarget()
const refreshEvent = new CustomEvent('refresh')

notifierCalendar.addEventListener('refresh', async () => {
    const events = await consultaEventos()
    $('#calendar').fullCalendar('removeEvents');
    $('#calendar').fullCalendar('addEventSource', events);
    $('#calendar').fullCalendar('rerenderEvents');
})

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

const updateDataEvents = async (id, payload) => {
    let result = await fetch(`${URL_PROJECT}cookTracking/update/${id}`, options(payload));
    result = await result.json();
    return result.data;
}

function updateURLWithDate(action) {
    const urlParams = new URLSearchParams(window.location.search);
    let year = urlParams.get('year');
    let month = urlParams.get('month');

    if (!year || !month) {
        year = new Date().getFullYear().toString();
        month = new Date().getMonth().toString();
    }

    let date = new Date(year, month);
    if (action === 'add') {
        date.setMonth(date.getMonth() + 1);
    } else if (action === 'subtract') {
        date.setMonth(date.getMonth() - 1);
    } else {
        const newUrl = window.location.pathname;
        window.history.pushState({ path: newUrl }, '', newUrl);
        return;
    }

    urlParams.set('year', date.getFullYear());
    urlParams.set('month', date.getMonth());

    const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
    window.history.pushState({ path: newUrl }, '', newUrl);
    console.log("Nueva URL: " + newUrl);
}

const consultaEventos = async () => {
    let date = moment();
    const queryParams = new URLSearchParams(window.location.search);
    if (queryParams.get("year") && queryParams.get("month")) {
        date.year(parseInt(queryParams.get("year")))
        date.month(parseInt(queryParams.get("month")))
    }
    let events = await queryDataEvents(date.format("YYYY"), date.format("MM")) ?? []
    events = events.map(({ dia, title, descripcion, id }) => {
        const _date = new Date(date.format("YYYY-MM-" + dia));
        _date.setUTCHours(_date.getUTCHours() + 5)
        return {
            id,
            dia,
            title: title ?? "Grays Cooked",
            descripcion,
            start: _date
        }
    })

    const baseValue = parseInt($("#valor-unitario").val().replace(".", ''));
    queryTotalTodo()
        .then((total_todo) => {
            $("#total-todo").val((parseInt(total_todo) * baseValue).toLocaleString('de-DE'))
        })
    $("#valor-total").val((events.length * baseValue).toLocaleString('de-DE'))
    return events;
}
const handleEventClickItem = (event) => {
    const { descripcion } = event.source.origArray.find((item) => item.id === event.id) ?? {}
    $('#detalle-tarea > textarea').val(descripcion);
    $('#detalle-tarea > textarea').css('height', '16rem');
    let buttonEstado = 'Editar';
    $('#revisarNota').modal('show');
    $('#commonButton').css({ display: 'block' });
    $('#commonButton').off('click');
    $('#commonButton').click(async function (e) {
        buttonEstado = e.target.textContent
        if (!window.contador) {
            window.contador = 0
        }
        console.log(window.contador++)
        switch (buttonEstado) {
            case 'Guardar':
                const d = $('#detalle-tarea > textarea').val();
                await updateDataEvents(event.id, { descripcion: d })
                    .catch((e) => alert(`Error al guardar: ${e}`))
                buttonEstado = 'Editar';
                $(this).text(buttonEstado);
                $(this).attr('class', 'btn btn-info');
                $('#detalle-tarea > textarea').attr('disabled', 'disabled');
                notifierCalendar.dispatchEvent(refreshEvent)
                break;

            case 'Editar':
                $('#detalle-tarea > textarea').removeAttr('disabled');
                buttonEstado = 'Guardar';
                $(this).text(buttonEstado);
                $(this).attr('class', 'btn btn-primary');
                break;
            default:
                break;
        }
    });

}
const loadCalendar = async () => {
    const events = await consultaEventos()
    $('#calendar').fullCalendar({
        defaultDate: new Date(moment().format()),
        dayRender: function (date, cell) {
            if (date.isSame(new Date(), "day")) {
                cell.css("background-color", "#D6EAF8");
            }
        },
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,basicWeek,basicDay'
        },
        editable: true,
        eventClick: function (event, jsEvent, view) {
            handleEventClickItem(event)
            console.log('Evento: ', event, jsEvent, view);
        },
        eventDrop: async function (event, delta, revertFunc) {
            await updateDataEvents(event.id, {
                descripcion: event.descripcion,
                date: event.start.format().split("T")[0]
            })
                .catch((e) => alert(`Error al guardar: ${e}`))
            notifierCalendar.dispatchEvent(refreshEvent)
            console.log(event);
        },
        eventLimit: true, // allow "more" link when too many events
        droppable: true, // this allows things to be dropped onto the calendar !!!
        drop: function (date, allDay) { // this function is called when something is dropped
            var originalEventObject = $(this).data('eventObject');
            storeDataEvents({
                date: date.format("YYYY-MM-DD"),
                title: originalEventObject.title,
                descripcion: originalEventObject.title
            })
                .then(() => {
                    notifierCalendar.dispatchEvent(refreshEvent)
                })
                .catch(error => {
                    console.error(error)
                    alert("Error al guardar")
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
            notifierCalendar.dispatchEvent(refreshEvent)
        });

        $('.fc-next-button').click(function () {
            updateURLWithDate('add')
            notifierCalendar.dispatchEvent(refreshEvent)
        });
        $('#calendar > div.fc-toolbar > div.fc-left > button').click(function () {
            updateURLWithDate()
            notifierCalendar.dispatchEvent(refreshEvent)
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
const mostrarModalDetalleData = async () => {
    const eventos = await consultaEventos();
    $('#commonButton').css({ display: 'none' });
    $('#revisarNota').modal('show');
    $('#detalle-tarea > textarea').val(eventos.map((item) => {
        const date = new Date()
        console.log(item)
        date.setDate(item.dia)
        return `${date.toDateString()}\n${item.title}\n${item.descripcion}\n`
    }).join('______________________________\r\n'));
    $('#detalle-tarea > textarea').css('height', '550');
}
//init
$.CalendarPage = new CalendarPage, $.CalendarPage.Constructor = CalendarPage

document.addEventListener("DOMContentLoaded", async () => {
    try {
        const newUrl = window.location.pathname;
        window.history.pushState({ path: newUrl }, '', newUrl);
        $("#resumen_p_mes").click(mostrarModalDetalleData)
        await $.CalendarPage.init()
    } catch (error) {
        console.log("ERROR CALENDARIO", error)
    }

})

