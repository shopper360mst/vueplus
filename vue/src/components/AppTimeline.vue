<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  item: {
    type: Object,
    default: () => ({
      sub_status: 'PROCESSING',
      submitted_date: 'DD/MM/YYYY',
      s_validate_date: '',
      delivered_date: '',
      delivery_date: '',
      delivery_status: '',
      delivery_details: '',
      invalid_sub_reason: '',
      reject_reason: '',
      product_ref: ''
    })
  }
})

// Helper to split multi-line strings for SVG tspans
const getRejectionLine = (reason, lineIndex) => {
  if (!reason) return ''
  // Use / as delimiter for multi-line rejection reasons
  const lines = String(reason).split('/')
  return lines[lineIndex] || ''
}

const getDeliveryDetailsLine = (details, lineIndex) => {
  if (!details) return ''
  const lines = details.split('\n')
  return lines[lineIndex] || ''
}
</script>

<template>
  <div class="w-full">
    <!-- Desktop version -->
    <div class="hidden md:block w-full h-full">
      <svg style="width:100%" viewBox="0 0 1060 465" fill="none" xmlns="http://www.w3.org/2000/svg">
        <g id="Framer Desktop" clip-path="url(#clip0_3796_4081)">
          <rect width="1060" height="465" fill="white"/>
          <g id="Line">
            <rect width="803" height="3" transform="translate(47 96)" fill="#006937"/>
          </g>
          <g id="FlexCol">
            <g id="Indicator_Widget1">
              <!-- Stage 1: Submission - Always show tick (completed) -->
              <g id="Tick_1">
                <circle id="Ellipse 1_2" cx="95" cy="95" r="48.5" fill="#006937" stroke="#006937" stroke-width="3"/>
                <g id="Frame">
                  <path id="Vector" fill-rule="evenodd" clip-rule="evenodd" d="M114.872 74.1919C115.617 74.6884 116.134 75.4604 116.309 76.3381C116.485 77.2157 116.304 78.1272 115.808 78.8719L93.308 112.622C93.0309 113.037 92.6651 113.385 92.2371 113.642C91.8091 113.899 91.3295 114.058 90.8328 114.107C90.3361 114.156 89.8348 114.094 89.3648 113.926C88.8948 113.758 88.468 113.488 88.115 113.135L74.615 99.6349C74.0188 98.9951 73.6943 98.1489 73.7097 97.2745C73.7251 96.4002 74.0793 95.566 74.6977 94.9476C75.3161 94.3292 76.1503 93.975 77.0246 93.9596C77.899 93.9442 78.7452 94.2687 79.385 94.8649L89.9735 105.453L110.192 75.1234C110.689 74.3794 111.462 73.8634 112.339 73.6887C113.217 73.514 114.128 73.695 114.872 74.1919Z" fill="white"/>
                </g>
              </g>
            </g>
            <text id="Submission" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="26" letter-spacing="0em">
              <tspan x="46" y="190">{{ t('timeline_stage_submission') }}</tspan>
            </text>
            <g id="Caption_Widget1">
              <text id="Submission Date" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="16" letter-spacing="0em">
                <tspan x="46" y="215">{{ t('timeline_submission_date') }}: {{ item.submitted_date || 'DD/MM/YYYY' }}</tspan>
              </text>
              <text id="TextStatus" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="15" letter-spacing="0em">
                <tspan x="46" y="235">{{ t('timeline_submission_received') }}</tspan>
                <tspan x="46" y="255">{{ t('timeline_submission_validation_time') }}</tspan>
                <tspan x="46" y="285">{{ t('timeline_submission_working_days') }}</tspan>
              </text>
            </g>
          </g>
          <g id="FlexCol_2">
            <g id="Indicator_Widget3">
              <!-- Stage 2: Processing -->
              <g id="Default_3" v-if="!item.sub_status || item.sub_status === 'PROCESSING'">
                <circle id="Ellipse 1_13" cx="489" cy="95" r="48.5" fill="#C8C8C8" stroke="#006937" stroke-width="3"/>
              </g>
              <g id="Tick_3" v-if="item.sub_status === 'APPROVED' || item.sub_status === 'DELIVERED'">
                <circle id="Ellipse 1_14" cx="489" cy="95" r="48.5" fill="#006937" stroke="#006937" stroke-width="3"/>
                <g id="Frame_13">
                  <path id="Vector_9" fill-rule="evenodd" clip-rule="evenodd" d="M508.872 74.1919C509.617 74.6884 510.134 75.4604 510.309 76.3381C510.485 77.2157 510.304 78.1272 509.808 78.8719L487.308 112.622C487.031 113.037 486.665 113.385 486.237 113.642C485.809 113.899 485.329 114.058 484.833 114.107C484.336 114.156 483.835 114.094 483.365 113.926C482.895 113.758 482.468 113.488 482.115 113.135L468.615 99.6349C468.019 98.9951 467.694 98.1489 467.71 97.2745C467.725 96.4002 468.079 95.566 468.698 94.9476C469.316 94.3292 470.15 93.975 471.025 93.9596C471.899 93.9442 472.745 94.2687 473.385 94.8649L483.973 105.453L504.192 75.1234C504.689 74.3794 505.462 73.8634 506.339 73.6887C507.217 73.514 508.128 73.695 508.872 74.1919Z" fill="white"/>
                </g>
              </g>
              <g id="Cross_3" v-if="item.sub_status === 'REJECTED'">
                <circle id="Ellipse 1_15" cx="489" cy="95" r="48.5" fill="#c1de98" stroke="#006937" stroke-width="3"/>
                <g id="Frame_14" clip-path="url(#clip6_3796_4081)">
                  <g id="Frame_15">
                    <path id="Vector_10" d="M475.06 75.6276C474.331 74.9486 473.368 74.579 472.372 74.5965C471.376 74.6141 470.426 75.0175 469.722 75.7218C469.017 76.426 468.614 77.3761 468.596 78.3719C468.579 79.3677 468.949 80.3314 469.627 81.0601L483.567 95.0001L469.627 108.94C469.25 109.292 468.947 109.716 468.737 110.188C468.527 110.659 468.414 111.168 468.405 111.684C468.396 112.2 468.491 112.713 468.684 113.192C468.877 113.67 469.165 114.105 469.53 114.47C469.895 114.835 470.33 115.123 470.808 115.316C471.287 115.51 471.8 115.604 472.316 115.595C472.832 115.586 473.341 115.473 473.812 115.263C474.284 115.053 474.708 114.75 475.06 114.373L489 100.433L502.94 114.373C503.292 114.75 503.716 115.053 504.188 115.263C504.659 115.473 505.168 115.586 505.684 115.595C506.2 115.604 506.713 115.51 507.192 115.316C507.67 115.123 508.105 114.835 508.47 114.47C508.835 114.105 509.123 113.67 509.316 113.192C509.509 112.713 509.604 112.2 509.595 111.684C509.586 111.168 509.473 110.659 509.263 110.188C509.053 109.716 508.75 109.292 508.372 108.94L494.432 95.0001L508.372 81.0601C509.051 80.3314 509.421 79.3677 509.404 78.3719C509.386 77.3761 508.983 76.426 508.278 75.7218C507.574 75.0175 506.624 74.6141 505.628 74.5965C504.632 74.579 503.669 74.9486 502.94 75.6276L489 89.5676L475.06 75.6276Z" fill="white"/>
                  </g>
                </g>
              </g>
            </g>
            <text id="Processing" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="25" letter-spacing="0em">
              <tspan x="440" y="190">{{ t('timeline_stage_processing') }}</tspan>
            </text>
            <g id="Caption_Widget3">
              <text id="Submission Date_6" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="16" letter-spacing="0em">
                <tspan x="440" y="215" v-if="item.sub_status && item.sub_status !== 'PROCESSING' && item.s_validate_date">{{ t('timeline_date') }}: {{ item.s_validate_date || 'DD/MM/YYYY' }}</tspan>
              </text>

              <!-- Status for PROCESSING -->
              <text id="TextStatus_Processing" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="16" letter-spacing="0em" v-if="item.sub_status === 'PROCESSING'">
                <tspan x="440" y="215">{{ t('timeline_processing_status_in_progress') }}</tspan>
                <tspan x="440" y="235">{{ t('timeline_processing_entry_received') }}</tspan>
                <tspan x="440" y="255">{{ t('timeline_processing_allow_7_days') }}</tspan>
                <tspan x="440" y="275">{{ t('timeline_processing_allow_7_days_2') }}</tspan>
              </text>

              <!-- Status for APPROVED -->
              <text id="TextStatus_Approved" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="16" letter-spacing="0em" v-if="item.sub_status === 'APPROVED' || item.sub_status === 'DELIVERED'">
                <tspan x="440" y="235">{{ t('timeline_processing_status_eligible') }}</tspan>
                <tspan x="440" y="255">{{ t('timeline_processing_congratulations') }}</tspan>
                <tspan x="440" y="275">{{ t('timeline_processing_packing_gift_new') }}</tspan>
                <tspan x="440" y="295">{{ t('timeline_processing_tracking_shared_new') }}</tspan>
                <tspan x="440" y="315">{{ t('timeline_processing_tracking_shared_new_2') }}</tspan>
              </text>

              <!-- Status for REJECTED -->
              <text id="TextStatus_Rejected" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="16" letter-spacing="0em" v-if="item.sub_status === 'REJECTED'">
                <tspan x="440" y="235">{{ t('timeline_processing_status_not_eligible') }}</tspan>
                <tspan x="440" y="255">{{ t('timeline_processing_unfortunately') }}</tspan>
                <tspan x="440" y="275">{{ t('timeline_processing_criteria') }}</tspan>
                <tspan x="440" y="295">{{ getRejectionLine(item.invalid_sub_reason || item.reject_reason, 0) }}</tspan>
                <tspan x="440" y="315">{{ getRejectionLine(item.invalid_sub_reason || item.reject_reason, 1) }}</tspan>
                <tspan x="440" y="335">{{ getRejectionLine(item.invalid_sub_reason || item.reject_reason, 2) }}</tspan>
                <tspan x="440" y="355">{{ getRejectionLine(item.invalid_sub_reason || item.reject_reason, 3) }}</tspan>
              </text> 
            </g>
          </g>
          <g id="FlexCol_3">
            <g id="Indicator_Widget4">
              <!-- Stage 3: On the Way -->
              <g id="Default_4" v-if="(!((item.delivered_date || item.delivery_date) && (item.delivery_status === 'OUT FOR DELIVERY' || item.delivery_status === 'DELIVERED') && item.delivery_details) && item.sub_status !== 'REJECTED') || item.sub_status === 'PROCESSING'">
                <circle id="Ellipse 1_16" cx="883" cy="95" r="48.5" fill="#C8C8C8" stroke="#006937" stroke-width="3"/>
              </g>
              <g id="Tick_4" v-if="(item.delivered_date || item.delivery_date) && (item.delivery_status === 'OUT FOR DELIVERY' || item.delivery_status === 'DELIVERED') && item.delivery_details">
                <circle id="Ellipse 1_17" cx="883" cy="95" r="48.5" fill="#006937" stroke="#006937" stroke-width="3"/>
                <g id="Frame_16">
                  <path id="Vector_11" fill-rule="evenodd" clip-rule="evenodd" d="M905.872 74.1919C906.617 74.6884 907.134 75.4604 907.309 76.3381C907.485 77.2157 907.304 78.1272 906.808 78.8719L884.308 112.622C884.031 113.037 883.665 113.385 883.237 113.642C882.809 113.899 882.329 114.058 881.833 114.107C881.336 114.156 880.835 114.094 880.365 113.926C879.895 113.758 879.468 113.488 879.115 113.135L865.615 99.6349C865.019 98.9951 864.694 98.1489 864.71 97.2745C864.725 96.4002 865.079 95.566 865.698 94.9476C866.316 94.3292 867.15 93.975 868.025 93.9596C868.899 93.9442 869.745 94.2687 870.385 94.8649L880.973 105.453L901.192 75.1234C901.689 74.3794 902.462 73.8634 903.339 73.6887C904.217 73.514 905.128 73.695 905.872 74.1919Z" fill="white"/>
                </g>
              </g>
              <g id="Cross_4" v-if="item.sub_status === 'REJECTED'">
                <circle id="Ellipse 1_18" cx="883" cy="95" r="48.5" fill="#c1de98" stroke="#006937" stroke-width="3"/>
                <g id="Frame_17" clip-path="url(#clip7_3796_4081)">
                  <g id="Frame_18">
                    <path id="Vector_12" d="M872.06 75.6276C871.331 74.9486 870.368 74.579 869.372 74.5965C868.376 74.6141 867.426 75.0175 866.722 75.7218C866.017 76.426 865.614 77.3761 865.596 78.3719C865.579 79.3677 865.949 80.3314 866.627 81.0601L880.567 95.0001L866.627 108.94C866.25 109.292 865.947 109.716 865.737 110.188C865.527 110.659 865.414 111.168 865.405 111.684C865.396 112.2 865.491 112.713 865.684 113.192C865.877 113.67 866.165 114.105 866.53 114.47C866.895 114.835 867.33 115.123 867.808 115.316C868.287 115.51 868.8 115.604 869.316 115.595C869.832 115.586 870.341 115.473 870.812 115.263C871.284 115.053 871.708 114.75 872.06 114.373L886 100.433L899.94 114.373C900.292 114.75 900.716 115.053 901.188 115.263C901.659 115.473 902.168 115.586 902.684 115.595C903.2 115.604 903.713 115.51 904.192 115.316C904.67 115.123 905.105 114.835 905.47 114.47C905.835 114.105 906.123 113.67 906.316 113.192C906.509 112.713 906.604 112.2 906.595 111.684C906.586 111.168 906.473 110.659 906.263 110.188C906.053 109.716 905.75 109.292 905.372 108.94L891.432 95.0001L905.372 81.0601C906.051 80.3314 906.421 79.3677 906.404 78.3719C906.386 77.3761 905.983 76.426 905.278 75.7218C904.574 75.0175 903.624 74.6141 902.628 74.5965C901.632 74.579 900.669 74.9486 899.94 75.6276L886 89.5676L872.06 75.6276Z" fill="white"/>
                  </g>
                </g>
              </g>
            </g>
            <text id="On the Way" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="25" letter-spacing="0em">
              <tspan x="834" y="189.65">{{ t('timeline_stage_on_the_way') }}</tspan>
            </text>
            <g id="Caption_Widget4">
              <!-- Date -->
              <text id="Submission Date_7" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="16" letter-spacing="0em">
                <tspan x="834" y="215" v-if="(item.delivered_date || item.delivery_date) && (item.delivery_status === 'OUT FOR DELIVERY' || item.delivery_status === 'DELIVERED') && item.delivery_details">{{ t('timeline_date') }}: {{ item.delivered_date || item.delivery_date || 'DD/MM/YYYY' }}</tspan>
              </text>
              <!-- Status messages -->
              <text id="TextStatus_5" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="16" letter-spacing="0em" v-if="(item.delivered_date || item.delivery_date) && (item.delivery_status === 'OUT FOR DELIVERY' || item.delivery_status === 'DELIVERED') && item.delivery_details">
                <tspan x="834" y="235">{{ t('timeline_ontheway_your_gift').replace('#N/A', '#' + (item.product_ref ? String(item.product_ref).padStart(5, '0') : 'N/A')) }}</tspan>
                <tspan x="834" y="255">{{ t('timeline_ontheway_on_way') }}</tspan>
                <tspan x="834" y="275">{{ t('timeline_ontheway_track_below') }}</tspan>
                <tspan x="834" y="295">{{ t('timeline_ontheway_tracking_number') }}</tspan>
                <tspan x="834" y="315">{{ t('timeline_ontheway_partner_website') }}</tspan>
                <tspan x="834" y="335">{{ t('timeline_ontheway_partner_website_2') }}</tspan>
                <tspan x="834" y="425">{{ t('timeline_ontheway_airway_bill') }}</tspan>
                <tspan x="834" y="445">{{ getDeliveryDetailsLine(item.delivery_details, 0) }}</tspan>
                <tspan x="834" y="465">{{ getDeliveryDetailsLine(item.delivery_details, 1) }}</tspan>
                <tspan x="834" y="485">{{ getDeliveryDetailsLine(item.delivery_details, 2) }}</tspan>
              </text>
            </g>
          </g>
        </g>
        <defs>
          <clipPath id="clip0_3796_4081">
            <rect width="1060" height="1200" fill="white"/>
          </clipPath>
          <clipPath id="clip6_3796_4081">
            <rect width="72" height="72" fill="white" transform="translate(453 59)"/>
          </clipPath>
          <clipPath id="clip7_3796_4081">
            <rect width="72" height="72" fill="white" transform="translate(847 59)"/>
          </clipPath>
        </defs>
      </svg>
    </div>

    <!-- Mobile version -->
    <div class="md:hidden w-full h-full">
      <svg style="width:100%" viewBox="0 0 495 750" fill="none" xmlns="http://www.w3.org/2000/svg">
        <g id="Framer Mobile">
          <rect width="495" height="750" fill="white"/>
          <g id="Line">
            <rect width="380" height="1.84786" transform="translate(73.924 96) rotate(90)" fill="#006937"/>
          </g>
          <g id="FlexCol">
            <g id="Indicator_Widget1">
              <g id="Tick_1">
                <circle id="Ellipse 1_2" cx="75" cy="105" r="39" fill="#006937" stroke="#006937" stroke-width="2"/>
                <g id="Frame">
                  <path id="Vector" fill-rule="evenodd" clip-rule="evenodd" d="M88.248 91.128C88.7445 91.459 89.0891 91.9737 89.2061 92.5588C89.3231 93.1439 89.2029 93.7515 88.872 94.248L73.872 116.748C73.6873 117.025 73.4434 117.257 73.1581 117.428C72.8728 117.599 72.553 117.705 72.2219 117.738C71.8908 117.771 71.5565 117.729 71.2432 117.617C70.9299 117.505 70.6453 117.325 70.41 117.09L61.41 108.09C61.0126 107.663 60.7962 107.099 60.8065 106.516C60.8168 105.934 61.0529 105.377 61.4651 104.965C61.8774 104.553 62.4335 104.317 63.0164 104.306C63.5993 104.296 64.1635 104.513 64.59 104.91L71.649 111.969L85.128 91.749C85.4595 91.253 85.9744 90.909 86.5594 90.7926C87.1445 90.6761 87.7519 90.7968 88.248 91.128Z" fill="white"/>
                </g>
              </g>
            </g>
            <g id="Caption_Widget1">
              <text id="Submission" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="22" letter-spacing="0em">
                <tspan x="135" y="76">{{ t('timeline_stage_submission') }}</tspan>
              </text>
              <text id="Submission Date" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="18" letter-spacing="0em">
                <tspan x="135" y="96">{{ t('timeline_submission_date') }}: {{ item.submitted_date || 'DD/MM/YYYY' }}</tspan>
              </text>
              <text id="TextStatus" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="18" letter-spacing="0em">
                <tspan x="135" y="116">{{ t('timeline_submission_received') }}</tspan>
                <tspan x="135" y="136">{{ t('timeline_submission_validation_time') }}</tspan>
                <tspan x="135" y="156">{{ t('timeline_submission_working_days') }}</tspan>
              </text>
            </g>
          </g>
          <g id="FlexCol_2">
            <g id="Indicator_Widget3">
              <g id="Default_3" v-if="!item.sub_status || item.sub_status === 'PROCESSING'">
                <circle id="Ellipse 1_13" cx="75" cy="280" r="39" fill="#C8C8C8" stroke="#006937" stroke-width="2"/>
              </g>
              <g id="Tick_3" v-if="item.sub_status === 'APPROVED' || item.sub_status === 'DELIVERED'">
                <circle id="Ellipse 1_14" cx="75" cy="280" r="39" fill="#006937" stroke="#006937" stroke-width="2"/>
                <g id="Frame_13">
                  <path id="Vector_9" fill-rule="evenodd" clip-rule="evenodd" d="M88.248 261.128C88.7445 261.459 89.0891 261.974 89.2061 262.559C89.3231 263.144 89.2029 263.752 88.872 264.248L73.872 286.748C73.6873 287.025 73.4434 287.257 73.1581 287.428C72.8728 287.599 72.553 287.705 72.2219 287.738C71.8908 287.771 71.5565 287.729 71.2432 287.617C70.9299 287.505 70.6453 287.325 70.41 287.09L61.41 278.09C61.0126 277.663 60.7962 277.099 60.8065 276.516C60.8168 275.934 61.0529 275.377 61.4651 274.965C61.8774 274.553 62.4335 274.317 63.0164 274.306C63.5993 274.296 64.1635 274.513 64.59 274.91L71.649 281.969L85.128 261.749C85.4595 261.253 85.9744 260.909 86.5594 260.793C87.1445 260.676 87.7519 260.797 88.248 261.128Z" fill="white"/>
                </g>
              </g>
              <g id="Cross_3" v-if="item.sub_status === 'REJECTED'">
                <circle id="Ellipse 1_15" cx="75" cy="280" r="39" fill="#c1de98" stroke="#006937" stroke-width="2"/>
                <g id="Frame_14">
                  <g id="Frame_15">
                    <path id="Vector_10" d="M65.7067 262.085C65.2209 261.632 64.5784 261.386 63.9145 261.398C63.2507 261.409 62.6173 261.678 62.1478 262.148C61.6783 262.617 61.4094 263.251 61.3976 263.915C61.3859 264.578 61.6324 265.221 62.085 265.707L71.3783 275L62.085 284.293C61.8332 284.528 61.6313 284.811 61.4912 285.125C61.3512 285.439 61.2759 285.779 61.2698 286.123C61.2637 286.467 61.327 286.809 61.4559 287.128C61.5848 287.447 61.7766 287.737 62.0199 287.98C62.2633 288.223 62.5531 288.415 62.8722 288.544C63.1913 288.673 63.533 288.673 63.8771 288.73C64.2212 288.724 64.5605 288.649 64.8748 288.509C65.1892 288.369 65.4721 288.167 65.7067 287.915L75 278.622L84.2933 287.915C84.5279 288.167 84.8108 288.369 85.1252 288.509C85.4395 288.649 85.7788 288.724 86.1229 288.73C86.4669 288.736 86.8087 288.673 87.1278 288.544C87.4469 288.415 87.7367 288.223 87.98 287.98C88.2234 287.737 88.4152 287.447 88.5441 287.128C88.673 286.809 88.7363 286.467 88.7302 286.123C88.7241 285.779 88.6488 285.439 88.5088 285.125C88.3687 284.811 88.1668 284.528 87.915 284.293L78.6217 275L87.915 265.707C88.3676 265.221 88.6141 264.578 88.6023 263.915C88.5906 263.251 88.3217 262.617 87.8522 262.148C87.3827 261.678 86.7493 261.409 86.0854 261.398C85.4216 261.386 84.7791 261.632 84.2933 262.085L75 271.378L65.7067 262.085Z" fill="white"/>
                  </g>
                </g>
              </g>
            </g>
            <g id="Caption_Widget3">
              <text id="Processing" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="22" letter-spacing="0em">
                <tspan x="135" y="250">{{ t('timeline_stage_processing') }}</tspan>
              </text>
              <text id="Submission Date_6" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="18" letter-spacing="0em">
                <tspan x="135" y="270" v-if="item.sub_status && item.sub_status !== 'PROCESSING' && item.s_validate_date">{{ t('timeline_date') }}: {{ item.s_validate_date || 'DD/MM/YYYY' }}</tspan>
              </text>

              <!-- Status for PROCESSING -->
              <text id="TextStatus_Processing" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="16" letter-spacing="0em" v-if="item.sub_status === 'PROCESSING'">
                <tspan x="135" y="270">{{ t('timeline_processing_status_in_progress') }}</tspan>
                <tspan x="135" y="290">{{ t('timeline_processing_entry_received') }}</tspan>
                <tspan x="135" y="310">{{ t('timeline_processing_allow_7_days') }}</tspan>
                <tspan x="135" y="330">{{ t('timeline_processing_allow_7_days_2') }}</tspan>
              </text>

              <!-- Status for APPROVED -->
              <text id="TextStatus_Approved" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="16" letter-spacing="0em" v-if="item.sub_status === 'APPROVED' || item.sub_status === 'DELIVERED'">
                <tspan x="135" y="290">{{ t('timeline_processing_status_eligible') }}</tspan>
                <tspan x="135" y="310">{{ t('timeline_processing_congratulations') }}</tspan>
                <tspan x="135" y="330">{{ t('timeline_processing_packing_gift_new') }}</tspan>
                <tspan x="135" y="350">{{ t('timeline_processing_tracking_shared_new') }}</tspan>
                <tspan x="135" y="370">{{ t('timeline_processing_tracking_shared_new_2') }}</tspan>
              </text>

              <!-- Status for REJECTED -->
              <text id="TextStatus_Rejected" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="16" letter-spacing="0em" v-if="item.sub_status === 'REJECTED'">
                <tspan x="135" y="290">{{ t('timeline_processing_status_not_eligible') }}</tspan>
                <tspan x="135" y="310">{{ t('timeline_processing_unfortunately') }}</tspan>
                <tspan x="135" y="330">{{ t('timeline_processing_criteria') }}</tspan>
                <tspan x="135" y="350">{{ getRejectionLine(item.invalid_sub_reason || item.reject_reason, 0) }}</tspan>
                <tspan x="135" y="370">{{ getRejectionLine(item.invalid_sub_reason || item.reject_reason, 1) }}</tspan>
                <tspan x="135" y="390">{{ getRejectionLine(item.invalid_sub_reason || item.reject_reason, 2) }}</tspan>
                <tspan x="135" y="410">{{ getRejectionLine(item.invalid_sub_reason || item.reject_reason, 3) }}</tspan>
              </text>
            </g>
          </g>
          <g id="FlexCol_3">
            <g id="Indicator_Widget4">
              <g id="Default_4" v-if="(!((item.delivered_date || item.delivery_date) && (item.delivery_status === 'OUT FOR DELIVERY' || item.delivery_status === 'DELIVERED') && item.delivery_details) && item.sub_status !== 'REJECTED') || item.sub_status === 'PROCESSING'">
                <circle id="Ellipse 1_16" cx="75" cy="450" r="39" fill="#C8C8C8" stroke="#006937" stroke-width="2"/>
              </g>
              <g id="Tick_4" v-if="(item.delivered_date || item.delivery_date) && (item.delivery_status === 'OUT FOR DELIVERY' || item.delivery_status === 'DELIVERED') && item.delivery_details">
                <circle id="Ellipse 1_17" cx="75" cy="450" r="39" fill="#006937" stroke="#006937" stroke-width="2"/>
                <g id="Frame_16">
                  <path id="Vector_11" fill-rule="evenodd" clip-rule="evenodd" d="M88.248 436.628C88.7445 436.959 89.0891 437.474 89.2061 438.059C89.3231 438.644 89.2029 439.252 88.872 439.748L73.872 462.248C73.6873 462.525 73.4434 462.757 73.1581 462.928C72.8728 463.099 72.553 463.205 72.2219 463.238C71.8908 463.271 71.5565 463.229 71.2432 463.117C70.9299 463.005 70.6453 462.825 70.41 462.59L61.41 453.59C61.0126 453.163 60.7962 452.016C60.8168 451.434 61.0529 450.877 61.4651 450.465C61.8774 450.053 62.4335 449.817 63.0164 449.806C63.5993 449.796 64.1635 450.013 64.59 450.41L71.649 457.469L85.128 437.249C85.4595 436.753 85.9744 436.409 86.5594 436.293C87.1445 436.176 87.7519 436.297 88.248 436.628Z" fill="white"/>
                </g>
              </g>
              <g id="Cross_4" v-if="item.sub_status === 'REJECTED'">
                <circle id="Ellipse 1_18" cx="75" cy="450" r="39" fill="#c1de98" stroke="#006937" stroke-width="2"/>
                <g id="Frame_17">
                  <g id="Frame_18">
                    <path id="Vector_12" d="M65.7067 437.585C65.2209 437.132 64.5784 436.886 63.9145 436.898C63.2507 436.909 62.6173 437.178 62.1478 437.648C61.6783 438.117 61.4094 438.751 61.3976 439.415C61.3859 440.078 61.6324 440.721 62.085 441.207L71.3783 450.5L62.085 459.793C61.8332 460.028 61.6313 460.311 61.4912 460.625C61.3512 460.939 61.2759 461.279 61.2698 461.623C61.2637 461.967 61.327 462.309 61.4559 462.628C61.5848 462.947 61.7766 463.237 62.0199 463.48C62.2633 463.723 62.5531 463.915 62.8722 464.044C63.1913 464.173 63.533 464.236 63.8771 464.23C64.2212 464.224 64.5605 464.149 64.8748 464.009C65.1892 463.869 65.4721 463.667 65.7067 463.415L75 454.122L84.2933 463.415C84.5279 463.667 84.8108 463.869 85.1252 464.009C85.4395 464.149 85.7788 464.224 86.1229 464.23C86.4669 464.236 86.8087 464.173 87.1278 464.044C87.4469 463.915 87.7367 463.723 87.98 463.48C88.2234 463.237 88.4152 462.947 88.5441 462.628C88.673 462.309 88.7363 461.967 88.7302 461.623C88.7241 461.279 88.6488 460.939 88.5088 460.625C88.3687 460.311 88.1668 460.028 87.915 459.793L78.6217 450.5L87.915 441.207C88.3676 440.721 88.6141 440.078 88.6023 439.415C88.5906 438.751 88.3217 438.117 87.8522 437.648C87.3827 437.178 86.7493 436.909 86.0854 436.898C85.4216 436.886 84.7791 437.132 84.2933 437.585L75 446.878L65.7067 437.585Z" fill="white"/>
                  </g>
                </g>
              </g>
            </g>
            <g id="Caption_Widget4">
              <text id="On the Way" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="22" letter-spacing="0em">
                <tspan x="135" y="430">{{ t('timeline_stage_on_the_way') }}</tspan>
              </text>
              <text id="Submission Date_7" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="18" letter-spacing="0em">
                <tspan x="135" y="450" v-if="(item.delivered_date || item.delivery_date) && (item.delivery_status === 'OUT FOR DELIVERY' || item.delivery_status === 'DELIVERED') && item.delivery_details">{{ t('timeline_date') }}: {{ item.delivered_date || item.delivery_date || 'DD/MM/YYYY' }}</tspan>
              </text>
              <text id="TextStatus_5" fill="black" xml:space="preserve" style="white-space: pre" font-family="NyDisplayBlack" font-size="16" letter-spacing="0em" v-if="(item.delivered_date || item.delivery_date) && (item.delivery_status === 'OUT FOR DELIVERY' || item.delivery_status === 'DELIVERED') && item.delivery_details">
                <tspan x="135" y="470">{{ t('timeline_ontheway_your_gift').replace('#N/A', '#' + (item.product_ref ? String(item.product_ref).padStart(5, '0') : 'N/A')) }} {{ t('timeline_ontheway_on_way') }}</tspan>
                <tspan x="135" y="490">{{ t('timeline_ontheway_track_below') }}</tspan>
                <tspan x="135" y="510">{{ t('timeline_ontheway_tracking_number') }}</tspan>
                <tspan x="135" y="530">{{ t('timeline_ontheway_partner_website') }}</tspan>
                <tspan x="135" y="550">{{ t('timeline_ontheway_partner_website_2') }}</tspan>
                <tspan x="135" y="640">{{ t('timeline_ontheway_airway_bill') }}</tspan>
                <tspan x="135" y="662">{{ getDeliveryDetailsLine(item.delivery_details, 0) }}</tspan>
                <tspan x="135" y="682">{{ getDeliveryDetailsLine(item.delivery_details, 1) }}</tspan>
                <tspan x="135" y="702">{{ getDeliveryDetailsLine(item.delivery_details, 2) }}</tspan>
              </text>
            </g>
          </g>
        </g>
        <defs>
          <clipPath id="clip0_3796_4254">
            <rect width="48" height="48" fill="white" transform="translate(51 81)"/>
          </clipPath>
          <clipPath id="clip1_3796_4254">
            <rect width="48" height="48" fill="white" transform="translate(51 256)"/>
          </clipPath>
          <clipPath id="clip2_3796_4254">
            <rect width="48" height="48" fill="white" transform="translate(51 426)"/>
          </clipPath>
        </defs>
      </svg>
    </div>
  </div>
</template>
