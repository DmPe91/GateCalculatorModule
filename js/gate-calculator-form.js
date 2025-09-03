(function ($, Drupal, once) {
  "use strict";

  Drupal.behaviors.gateCalculator = {
    attach: function (context, settings) {
      // Хранилище для сообщений
      if (!Drupal.gateCalculatorMessages) {
        Drupal.gateCalculatorMessages = {};
      }

      // Функция для отображения/скрытия сообщений
      function updateMessages() {
        var $priceContainer = $("#price-container");

        // Удаляем все временные сообщения
        $(".temporary-message").remove();

        // Добавляем активные сообщения
        $.each(Drupal.gateCalculatorMessages, function (fieldId, message) {
          if (message) {
            $priceContainer.prepend(
              $(
                '<div class="messages messages--warning temporary-message">' +
                  message +
                  "</div>"
              ).attr("data-field", fieldId)
            );
          }
        });
      }

      // Функция для создания кнопок +/- для полей ввода
      function addSpinButtons(element) {
        var $input = $(element);
        var $wrapper = $input
          .wrap('<div class="number-input-wrapper"></div>')
          .parent();

        // Кнопка "-"
        $('<button type="button">-</button>')
          .addClass("number-input-btn number-input-minus")
          .on("click", function () {
            var currentVal = parseInt($input.val());
            var step = parseInt($input.attr("step") || 10);
            var min = parseInt($input.attr("min") || 0);
            var newVal = currentVal - step;

            // Если достигли минимума, показываем сообщение
            if (newVal < min) {
              // Устанавливаем минимальное значение
              $input.val(min).trigger("change");

              // Сохраняем сообщение в хранилище
              var fieldName =
                $input.attr("id") === "edit-shirina-vorot"
                  ? "ширины"
                  : "высоты";
              Drupal.gateCalculatorMessages[$input.attr("id")] =
                "Минимальное значение " +
                fieldName +
                " достигнуто. Для меньших размеров расчет индивидуальный.";

              return;
            } else {
              // Удаляем сообщение для этого поля, если значение изменилось
              delete Drupal.gateCalculatorMessages[$input.attr("id")];
            }

            // Если все ок, меняем значение
            $input.val(newVal).trigger("change");
          })
          .appendTo($wrapper);

        // Кнопка "+"
        $('<button type="button">+</button>')
          .addClass("number-input-btn number-input-plus")
          .on("click", function () {
            var currentVal = parseInt($input.val());
            var step = parseInt($input.attr("step") || 10);
            var max = parseInt($input.attr("max") || 10000);
            var newVal = currentVal + step;

            // Если достигли максимума, показываем сообщение
            if (newVal > max) {
              // Устанавливаем максимальное значение
              $input.val(max).trigger("change");

              // Сохраняем сообщение в хранилище
              var fieldName =
                $input.attr("id") === "edit-shirina-vorot"
                  ? "ширины"
                  : "высоты";
              Drupal.gateCalculatorMessages[$input.attr("id")] =
                "Максимальное значение " +
                fieldName +
                " достигнуто. Для больших размеров расчет индивидуальный.";

              return;
            } else {
              // Удаляем сообщение для этого поля, если значение изменилось
              delete Drupal.gateCalculatorMessages[$input.attr("id")];
            }

            $input.val(newVal).trigger("change");
          })
          .appendTo($wrapper);
      }

      // Применяем к нужным полям с использованием once()
      $(once("add-buttons", "#edit-shirina-vorot", context)).each(function () {
        addSpinButtons(this);
      });

      $(once("add-buttons", "#edit-vysota-vorot", context)).each(function () {
        addSpinButtons(this);
      });

      // Блокировка поля "Заливка основания" для распашных ворот
      function toggleFoundationField() {
        var gateType = $("#edit-tip-vorot").val();
        var foundationField = $("#edit-zalivka-osnovaniya");
        if (gateType === "swing") {
          foundationField.val("bez_zalivki").trigger("change");
          foundationField.prop("disabled", true);
          foundationField.parent().addClass("form-disabled");
        } else {
          foundationField.prop("disabled", false);
          foundationField.parent().removeClass("form-disabled");
        }
      }

      // Вызываем при загрузке и при изменении типа ворот
      toggleFoundationField();
      $("#edit-tip-vorot", context).on("change", toggleFoundationField);

      // Обновляем сообщения после AJAX-запросов
      $(document).ajaxComplete(function () {
        updateMessages();
      });

      // Инициализируем сообщения при загрузке
      updateMessages();
      // НОВАЯ ЛОГИКА: Управление формой заявки
      function showOrderForm() {
        // Скрываем кнопки расчета и заявки
        $(".calculate-button, .order-button").addClass("button-hidden");

        // Блокируем поля формы
        $(".gate-calculator-fields").addClass("form-fields-disabled");

        // Показываем поля личной информации
        $("#personal-info-container")
          .removeClass("personal-info-hidden")
          .addClass("personal-info-visible");
      }

      function continueCalculation() {
        // Показываем кнопки расчета и заявки
        $(".calculate-button, .order-button").removeClass("button-hidden");

        // Разблокируем поля формы
        $(".gate-calculator-fields").removeClass("form-fields-disabled");

        // Скрываем поля личной информации
        $("#personal-info-container")
          .removeClass("personal-info-visible")
          .addClass("personal-info-hidden");

        // Очищаем поля
        $("#edit-name, #edit-phone").val("");
      }

      // Обработчик для кнопки "Оставить заявку"
      $(once("order-button", ".order-button", context)).on(
        "click",
        function (e) {
          //e.preventDefault();
          showOrderForm();
        }
      );

      // Обработчик для кнопки "Продолжить расчет"
      $(once("continue-button", ".continue-calculation-button", context)).on(
        "click",
        function (e) {
          e.preventDefault();
          continueCalculation();
        }
      );

      // Сохраняем текущие данные формы для отправки
      Drupal.gateCalculatorCurrentData = {};

      $(document).ajaxComplete(function (event, xhr, settings) {
        // Сохраняем текущие данные формы
        Drupal.gateCalculatorCurrentData = {
          tip_vorot: $("#edit-tip-vorot").val(),
          shirina_vorot: $("#edit-shirina-vorot").val(),
          vysota_vorot: $("#edit-vysota-vorot").val(),
          kalitka: $("#edit-kalitka").val(),
          pokraska: $("#edit-pokraska").val(),
          zapolnenie: $("#edit-zapolnenie").val(),
          avtomatika: $("#edit-avtomatika").val(),
          zalivka_osnovaniya: $("#edit-zalivka-osnovaniya").val(),
          zalivka_stoek: $("#edit-zalivka-stoek").val(),
        };
      });
    },
  };
})(jQuery, Drupal, once);
