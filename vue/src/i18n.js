import { createI18n } from 'vue-i18n'

const messages = {
  en: {
    common: {
      submit: 'SUBMIT',
      cancel: 'CANCEL',
      close: 'CLOSE | X',
      ok: 'OK'
    },
    toast: {
      success: 'Success',
      error: 'Error'
    },
    gate: {
      titleDob: 'Enter Year of Birth',
      confirmMobile: 'I CONFIRM THAT I AM NON-MUSLIM<BR>AGED 21 YEARS OLD AND ABOVE',
      confirmDesktop: 'I CONFIRM THAT I AM NON-MUSLIM AGED 21 YEARS OLD AND ABOVE',
      subtitle: 'Please share this site only with individuals aged 21+ and those who are non-Muslims.',
      company: 'Carlsberg Marketing Sdn Bhd',
      simpleTitle1: 'This website is strictly for non-Muslims aged 21 and above.',
      simpleTitle2: 'By entering this website, you agree you are a non-Muslim aged 21 years old and above.',
      yes: 'Yes, I am',
      no: 'No, I am not',
      rememberMe: 'Remember me on this device (don\'t tick if this is a shared computer)',
      disclaimer: 'If you drink, don\'t drive.'
    },
    footer: {
      terms_conditions: 'TERMS & CONDITIONS',
      faq: 'FAQ',
      privacy_policy: 'PRIVACY POLICY',
      beers_love: 'BEERS YOU LOVE',
      responsible_drinking: 'RESPONSIBLE DRINKING',
      faq_footer_1: 'Carlsberg Marketing Sdn Bhd 198501008089 (140534-M) ',
      faq_footer_2: '55, Persiaran Selangor, Seksyen 15, 40200 Shah Alam, Selangor, Malaysia.',
      faq_footer_3: 'For 21+ non-Muslims only. Please only share this site to those aged 21+ and non-Muslims.',
      disclaimer_1: '',
      disclaimer_2: 'If you drink, don\'t drive.'
    }
  },
  ch: {
    common: {
      submit: '提交',
      cancel: '取消',
      close: '关闭 | X',
      ok: '确定'
    },
    toast: {
      success: '成功',
      error: '错误'
    },
    gate: {
      titleDob: '请输入出生年份',
      confirmMobile: '我确认我不是穆斯林<BR>且年龄在21岁或以上',
      confirmDesktop: '我确认我不是穆斯林且年龄在21岁或以上',
      subtitle: '请仅与21岁及以上的非穆斯林分享此网站。',
      company: '皇帽市场有限公司',
      simpleTitle1: '本网站仅供21岁及以上的非穆斯林使用。',
      simpleTitle2: '进入本网站即表示您同意您是21岁及以上的非穆斯林。',
      yes: '是的，我是',
      no: '不，我不是',
      rememberMe: '在此设备上记住我（如果是共用电脑，请勿勾选）',
      disclaimer: '酒后不开车。'
    },
    footer: {
      terms_conditions: '条款与条件',
      faq: '常见问题',
      privacy_policy: '隐私政策',
      beers_love: '您喜爱的啤酒',
      responsible_drinking: '理性饮酒',
      faq_footer_1: 'Carlsberg Marketing Sdn Bhd 198501008089 (140534-M) ',
      faq_footer_2: '55, Persiaran Selangor, Seksyen 15, 40200 Shah Alam, Selangor, Malaysia.',
      faq_footer_3: '仅限于21岁及以上非穆斯林。请仅将此网站分享给21岁以上的非穆斯林人士。',
      disclaimer_1: '',
      disclaimer_2: '酒后不开车。'
    }
  }
}

const i18n = createI18n({
  legacy: false,
  locale: 'en',
  fallbackLocale: 'en',
  messages
})

export default i18n
