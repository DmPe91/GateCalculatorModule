<?php

namespace Drupal\gate_calculator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Форма калькулятора стоимости ворот.
 */
class GateCalculatorForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gate_calculator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Загружаем сохраненные значения
    $price = $form_state->get('calculated_price') ?? 0;
    $message = $form_state->get('calculation_message') ?? '';
    $image_path = $form_state->get('gate_image_path') ?? $this->getDefaultImagePath();

    $is_block = \Drupal::routeMatch()->getRouteName() !== 'gate_calculator.calculator';

    if (!$is_block) {
      $form['#prefix'] = '<div id="gate-calculator-block-wrapper">';
      $form['#suffix'] = '</div>';
    } //else {
      //$form['#prefix'] = '<div id="gate-calculator-form-wrapper">';
      //$form['#suffix'] = '</div>';
    //}

    // Контейнер для основного контента
    $form['content_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['gate-calculator-content']],
    ];

    // Контейнер для полей формы
    $form['content_container']['form_fields'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['gate-calculator-fields']],
    ];

    // Контейнер для изображения
    $form['content_container']['image_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['gate-calculator-image']],
    ];

    $form['content_container']['image_container']['gate_image'] = [
      '#markup' => '<div id="gate-image-preview"><img src="' . $image_path . '" alt="Предварительный просмотр ворот" /></div>',
    ];

    // ПОЛЕ: Тип ворот
    $form['content_container']['image_container']['tip_vorot'] = [
      '#type' => 'radios',
      '#title' => $this->t('Тип ворот'),
      '#options' => [
        'console' => $this->t('Ворота консольные'),
        'swing' => $this->t('Ворота распашные'),
        'sliding' => $this->t('Ворота откатные'),
        'folding' => $this->t('Ворота складные'),
      ],
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('tip_vorot', 'console'),
      '#ajax' => [
        'callback' => '::updateImageAndPriceCallback',
        'wrapper' => 'gate-calculator-form-wrapper',
        'event' => 'change',
      ],
    ];

    // ПОЛЕ: Ширина ворот
    $form['content_container']['form_fields']['shirina_vorot'] = [
      '#type' => 'number',
      '#title' => $this->t('Ширина проема (мм)'),
      '#min' => 2500,
      '#max' => 5100,
      '#step' => 10, 
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('shirina_vorot', 4000),
      '#ajax' => [
        'callback' => '::updateImageAndPriceCallback',
        'wrapper' => 'gate-calculator-form-wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Обновление расчета...'),
        ],
      ],
    ];

    // ПОЛЕ: Высота ворот
    $form['content_container']['form_fields']['vysota_vorot'] = [
      '#type' => 'number',
      '#title' => $this->t('Высота (мм)'),
      '#min' => 1400,
      '#max' => 2400,
      '#step' => 10,
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('vysota_vorot', 2000),
      '#ajax' => [
        'callback' => '::updateImageAndPriceCallback',
        'wrapper' => 'gate-calculator-form-wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Обновление расчета...'),
        ],
      ],
    ];

    // ПОЛЕ: Покраска
    $form['content_container']['form_fields']['pokraska'] = [
      '#type' => 'select',
      '#title' => $this->t('Покраска'),
      '#options' => [
        'bez_pokraski' => $this->t('Без покраски.'),
        'pokraska' => $this->t('Покраска.'),
      ],
      '#default_value' => $form_state->getValue('pokraska', 'bez_pokraski'),
    ];

    // ПОЛЕ: Калитка
    $form['content_container']['form_fields']['kalitka'] = [
      '#type' => 'select',
      '#title' => $this->t('Калитка'),
      '#options' => [
        'net' => $this->t('Без калитки'),
        //'bez_ustanovki_kalitki' => $this->t('Без установки калитки'),
        'kalitka_bez_ustanovki' => $this->t('Калитка без установки.'),
        's_kalitkoy' => $this->t('Калитка с установкой'),
      ],
      '#default_value' => $form_state->getValue('kalitka', 'net'),
    ];

    // ПОЛЕ: Заливка основания
    $form['content_container']['form_fields']['zalivka_osnovaniya'] = [
      '#type' => 'select',
      '#title' => $this->t('Заливка основания'),
      '#options' => [
        'bez_zalivki' => $this->t('Без заливки основания.'),
        's_zalivkoy' => $this->t('С заливкой основания.'),
      ],
      '#default_value' => $form_state->getValue('zalivka_osnovaniya', 'bez_zalivki'),
    ];

    // ПОЛЕ: Заливка стоек
    $form['content_container']['form_fields']['zalivka_stoek'] = [
      '#type' => 'select',
      '#title' => $this->t('Заливка стоек'),
      '#options' => [
        'bez_zalivki' => $this->t('Без заливки стоек.'),
        's_zalivkoy' => $this->t('С заливкой стоек.'),
      ],
      '#default_value' => $form_state->getValue('zalivka_stoek', 'bez_zalivki'),
    ];

    // ПОЛЕ: Заполнение
    $form['content_container']['form_fields']['zapolnenie'] = [
      '#type' => 'select',
      '#title' => $this->t('Заполнение полотна'),
      '#options' => [
        'none' => $this->t('Без заполнения.'),
        'prof_list' => $this->t('Профлист.'),
        'shtaketnik_1' => $this->t('Штакетник односторонний.'),
        'shtaketnik_2' => $this->t('Двухсторонний штакетник.'),
        'zalyuzi' => $this->t('Жалюзи.'),
        '3d_setka' => $this->t('3D сетка.'),
      ],
      '#default_value' => $form_state->getValue('zapolnenie', 'none'),
      '#ajax' => [
        'callback' => '::updateImageAndPriceCallback',
        'wrapper' => 'gate-calculator-form-wrapper',
        'event' => 'change',
      ],
    ];

    // ПОЛЕ: Автоматика
    $form['content_container']['form_fields']['avtomatika'] = [
      '#type' => 'select',
      '#title' => $this->t('Автоматика'),
      '#options' => [
        'bez_avtomatiki' => $this->t('Без автоматики.'),
        'polniy_komplekt' => $this->t('Полный комплект автоматики'),
        "electro-zamok" => $this->t("Электромеханический замок на калитку")
      ],
      '#default_value' => $form_state->getValue('avtomatika', 'bez_avtomatiki'),
    ];

    // Контейнер для отображения цены
    $form['content_container']['image_container']['price_display'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'price-container', 'class' => ['gate-calculator-price']],
    ];

    $form['content_container']['image_container']['price_display']['price'] = [
      '#markup' => '<div class="calculated-price">' . $this->formatPrice($price) . '</div>',
    ];

    $form['content_container']['image_container']['price_display']['disclaimer'] = [
      '#markup' => '<div class="price-disclaimer">*Цены указаны приближенные к действительности. Для более точных расчетов просим связаться с нами по телефону.</div>',
    ];

    if (!empty($message)) {
      $form['content_container']['image_container']['price_display']['message'] = [
        '#markup' => '<div class="messages messages--warning">' . $message . '</div>',
      ];
    }

    // Контейнер для личной информации (скрыт по умолчанию)
    $form['personal_info'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'personal-info-container',
        'class' => ['personal-info-hidden'],
      ],
    ];

    $form['personal_info']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ваше имя'),
      '#attributes' => ['placeholder' => $this->t('Иван Иванов')],
    ];

    $form['personal_info']['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Телефон'),
      '#attributes' => [
        'placeholder' => $this->t('+7 (999) 999-99-99'),
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['form-actions']],
    ];

    // Кнопка "Рассчитать"
    $form['actions']['calculate'] = [
      '#type' => 'button',
      '#value' => $this->t('Рассчитать'),
      '#ajax' => [
        'callback' => '::calculatePriceCallback',
        'wrapper' => 'gate-calculator-form-wrapper',
      ],
      '#attributes' => [
        'class' => ['calculate-button'],
      ],
    ];

    // Кнопка "Оставить заявку"
    $form['actions']['order_evaluation'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Заказать оценку'),
      '#attributes' => [
        'class' => ['order-button'],
        'type' => 'button', // ключевой момент
      ],
    ];

    // Кнопка "Отправить заявку" (в контейнере личной информации)
    $form['personal_info']['submit_order'] = [
      '#type' => 'submit',
      '#value' => $this->t('Отправить заявку'),
      '#attributes' => [
        'class' => ['order-submit-button'],
      ],
    ];

    // Кнопка "Продолжить расчет" (в контейнере личной информации)
    $form['personal_info']['continue_calculation'] = [
      '#type' => 'button',
      '#value' => $this->t('Продолжить расчет'),
      '#attributes' => [
        'class' => ['continue-calculation-button'],
      ],
    ];

    // Подключаем CSS и JS
    $form['#attached']['library'][] = 'gate_calculator/form_styles';

    return $form;
  }

  /**
   * AJAX callback для расчета цены.
   */
  public function calculatePriceCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Рассчитываем цену и получаем путь к изображению
    $price_data = $this->calculatePriceAndImage($form_state);
    $price = $price_data['price'];
    $image_path = $price_data['image_path'];
    $message = $price_data['message'];

    // Сохраняем значения
    $form_state->set('calculated_price', $price);
    $form_state->set('calculation_message', $message);
    $form_state->set('gate_image_path', $image_path);
    $form_state->setRebuild(TRUE);

    // Обновляем изображение
    $new_image_html = '<div id="gate-image-preview"><img src="' . $image_path . '" alt="Предварительный просмотр ворот" /></div>';
    $response->addCommand(new HtmlCommand('#gate-image-preview', $new_image_html));

    // Обновляем блок с ценой
    $new_price_html = '<div class="calculated-price">' . $this->formatPrice($price) . '</div>';
    $new_price_html .= '<div class="price-disclaimer">*Цены указаны приближенные к действительности. Для более точных расчетов просим связаться с нами по телефону.</div>';
    if (!empty($message)) {
        $new_price_html .= '<div class="messages messages--warning">' . $message . '</div>';
    }
    $response->addCommand(new HtmlCommand('#price-container', $new_price_html));

    return $response;
  }

  /**
   * AJAX callback для обновления изображения и цены при изменении полей.
   */
  public function updateImageAndPriceCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Рассчитываем цену и получаем путь к изображению
    $price_data = $this->calculatePriceAndImage($form_state);
    $price = $price_data['price'];
    $image_path = $price_data['image_path'];
    $message = $price_data['message'];

    // Сохраняем значения
    $form_state->set('calculated_price', $price);
    $form_state->set('calculation_message', $message);
    $form_state->set('gate_image_path', $image_path);
    $form_state->setRebuild(TRUE);

    // Обновляем изображение
    $new_image_html = '<div id="gate-image-preview"><img src="' . $image_path . '" alt="Предварительный просмотр ворот" /></div>';
    $response->addCommand(new HtmlCommand('#gate-image-preview', $new_image_html));

    // Обновляем блок с ценой
    $new_price_html = '<div class="calculated-price">' . $this->formatPrice($price) . '</div>';
    $new_price_html .= '<div class="price-disclaimer">*Цены указаны приближенные к действительности. Для более точных расчетов просим связаться с нами по телефону.</div>';
    
    if (!empty($message)) {
      $new_price_html .= '<div class="messages messages--warning">' . $message . '</div>';
    }
    
    $response->addCommand(new HtmlCommand('#price-container', $new_price_html));

    return $response;
  }

  /**
   * Логика расчета цены и выбора изображения.
   */
  private function calculatePriceAndImage(FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $price = 0;
    $message = '';

    $width = floatval($values['shirina_vorot']);
    $height = floatval($values['vysota_vorot']);

    // 1. Проверка на складные ворота - ВСЕГДА ПЕРВОЙ!
    if ($values['tip_vorot'] === 'folding') {
      return [
        'price' => -1,
        'message' => 'Ворота складные рассчитываются индивидуально',
        'image_path' => $this->getDefaultImagePath(),
      ];
    }

    // 2. Проверка значений НИЖЕ минимума
    if ($width < 2500 || $height < 1400) {
      return [
        'price' => -1,
        'message' => 'Рассчитывается индивидуально (значения ниже минимальных)',
        'image_path' => $this->getDefaultImagePath(),
      ];
    }

    // 3. Проверка значений ВЫШЕ максимума
    if ($width > 5100 || $height > 2400) {
      return [
        'price' => -1,
        'message' => 'Рассчитывается индивидуально (значения выше максимальных)',
        'image_path' => $this->getDefaultImagePath(),
      ];
    }

    // --- ЕСЛИ ДОШЛИ ДО СЮДА, ЗНАЧИТ РАСЧЕТ СТАНДАРТНЫЙ ---

    $width_surcharge = 0;
    $auto_price = 0;
    $kalitka_s_ustanovkoy_price = 0;
    $kalitka_bez_ustanovki_price = 0;

    // 1. Базовые цены по типам ворот
    switch ($values['tip_vorot']) {
      case 'console':
        $price += 46000;
        $width_surcharge = 7000;
        $auto_price = 42000;
        $kalitka_s_ustanovkoy_price = 25000;
        $kalitka_bez_ustanovki_price = 20000;
        break;

      case 'swing':
        $price += 44000;
        $width_surcharge = 4000;
        $auto_price = 56000;
        $kalitka_s_ustanovkoy_price = 20000;
        $kalitka_bez_ustanovki_price = 15000;
        break;

      case 'sliding':
        $price += 38000;
        $width_surcharge = 3000;
        $auto_price = 42000;
        $kalitka_s_ustanovkoy_price = 20000;
        $kalitka_bez_ustanovki_price = 15000;
        break;

      default:
        $price = 0;
    }

    // 2. Надбавка за ширину (если от 4.20 до 5.0)
    if ($values['shirina_vorot'] > 4.20) {
      $price += $width_surcharge;
    }

    // 3. Покраска
    if ($values['pokraska'] == 'pokraska') {
      $price += 5000;
    }

    // 4. Калитка
     switch ($values['kalitka']) {
       case 's_kalitkoy':
      $price += $kalitka_s_ustanovkoy_price;
        break;

      case 'kalitka_bez_ustanovki':
        $price += 16000;
        break;

      case 'net':
        if ($values['tip_vorot'] !== 'folding' && $values['zalivka_osnovaniya'] == 'bez_zalivki') {
          $price += $kalitka_bez_ustanovki_price;
      }
        break;
    }


    // 5. Заливка основания (НЕ доступно для распашных!)
    if ($values['tip_vorot'] !== 'swing' && $values['zalivka_osnovaniya'] == 's_zalivkoy') {
      $price += 7000;
    }

    // 6. Заливка стоек
    if ($values['zalivka_stoek'] == 's_zalivkoy') {
      $price += 8000;
    }

    // 7. Заполнение
    switch ($values['zapolnenie']) {
      case 'prof_list':
        $price += 7000;
        break;

      case 'shtaketnik_1':
        $price += 8000;
        break;

      case 'shtaketnik_2':
        $price += 18000;
        break;

      case 'zalyuzi':
        $price += 37000;
        break;

      case '3d_setka':
        $price += 7000;
        break;
    }

    // 8. Автоматика
    switch($values['avtomatika']) {
      case 'electro-zamok':
        $price += 10000;
        break;
      case 'polniy_komplekt':
        $price += $auto_price;
        break;
    }

    // Формируем путь к изображению
    $image_filename = $values['tip_vorot'] . '-' . $values['zapolnenie'] . '.jpg';

    return [
      'price' => $price,
      'message' => $message,
      'image_path' => $this->getImagePath($image_filename),
    ];
  }

  /**
   * Вспомогательная функция для получения пути к изображению.
   */
  private function getImagePath($filename) {
     // Укажите правильное машинное имя вашей темы!
    $theme_path = \Drupal::service('extension.path.resolver')->getPath('theme', 'vesta_theme');
    $image_relative_path = 'image/gate-configurator/' . $filename;
    $image_full_path = $theme_path . '/' . $image_relative_path;

    // Проверяем, существует ли файл. Если нет - возвращаем заглушку.
    if (!file_exists($image_full_path)) {
        return $this->getDefaultImagePath();
    }
    return base_path() . $theme_path . '/' . $image_relative_path;
  }

  /**
   * Получение пути к изображению по умолчанию.
   */
  private function getDefaultImagePath() {
      // Укажите правильное машинное имя вашей темы!
    $theme_path = \Drupal::service('extension.path.resolver')->getPath('theme', 'vesta_theme');
    $default_image_path = $theme_path . '/image/gate-configurator/console-none.jpg';
    
    // Проверяем, существует ли файл заглушки
    if (file_exists($default_image_path)) {
        return base_path() . $default_image_path;
    }
    
    // Если заглушка не найдена, возвращаем пустую строку или базовый путь
    return base_path() . $theme_path . '/image/gate-configurator/';
  }

  /**
   * Форматирование цены.
   */
  private function formatPrice($price) {
     if ($price == -1) {
      return 'Цена: от 130 000* руб.';
    }
    return 'Цена: ' . number_format($price, 0, '', ' ') . '* руб.';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Обработка отправки формы заявки
    $values = $form_state->getValues();
    
    try {
        // Используем уже рассчитанную цену или 0
        $price = $form_state->get('calculated_price') ?? 0;
        
        // Сохраняем заявку в виде ноды
        $node = Node::create([
            'type' => 'rasschet_stoimosti',
            'title' => 'Заявка на расчет от ' . date('Y-m-d H:i:s'),
            'field_tip_vorot' => $values['tip_vorot'] ?? '',
            'field_shirina_vorot' => $values['shirina_vorot'] ?? 0,
            'field_vysota_vorot' => $values['vysota_vorot'] ?? 0,
            'field_kalitka' => $values['kalitka'] ?? '',
            'field_pokraska' => $values['pokraska'] ?? '',
            'field_zapolnenie' => $values['zapolnenie'] ?? '',
            'field_avtomatika' => $values['avtomatika'] ?? '',
            'field_zalivka_osnovaniya' => $values['zalivka_osnovaniya'] ?? '',
            'field_zalivka_stoek' => $values['zalivka_stoek'] ?? '',
            'field_rasschetnaya_cena' => $price,
            'field_imya_polzovatelya' => $values['name'] ?? '',
            'field_telefon_polzovatelya' => $values['phone'] ?? '',
            'status' => 1,
        ]);

        $node->save();
        
        \Drupal::messenger()->addMessage($this->t('Спасибо за заявку! Наш менеджер свяжется с вами в ближайшее время.'));
        
        \Drupal::logger('gate_calculator')->info('Заявка на расчет сохранена: @id', ['@id' => $node->id()]);

    } catch (\Exception $e) {
        \Drupal::logger('gate_calculator')->error('Ошибка сохранения заявки: @error', ['@error' => $e->getMessage()]);
        \Drupal::messenger()->addError($this->t('Произошла ошибка при сохранении заявки.'));
    }
  }

 

}