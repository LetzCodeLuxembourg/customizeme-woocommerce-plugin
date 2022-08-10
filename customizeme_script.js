;(function () {
  const { productLink, price, currency, settings, dontHideVariations, customInjectTo } = customizemeScriptData

  const dataInput = document.getElementById('customizeme_data_input')
  const priceInput = document.getElementById('customizeme_price_input')
  const priceSpan = document.getElementById('customizeme_price_span')

  if (!dataInput || !priceInput || !priceSpan || !productLink) return

  let productVariations = null
  let jQueryObject = null
  const variationsForm = document.querySelector('.variations_form.cart')

  const variationsTable = document.querySelector('table.variations')
  const skuWrapper = document.querySelector('span.sku_wrapper')
  const variantionInfo = document.querySelector('.woocommerce-variation.single_variation')
  const singleVariation = document.querySelector('.woocommerce-variation.single_variation')

  if (variationsTable && !dontHideVariations) variationsTable.style.display = 'none'
  if (skuWrapper) skuWrapper.style.display = 'none'
  if (variantionInfo) variantionInfo.style.display = 'none'
  if (singleVariation) singleVariation.remove()

  const iframe = document.createElement('iframe')
  iframe.style.display = 'none'
  iframe.src = productLink
  iframe.onload = () => {
    if (variationsForm) {
      jQueryObject = Object.values(variationsForm).find(el => el.events && el.events.found_variation)
      if (jQueryObject) productVariations = jQueryObject.events.change[0].data.variationForm.variationData
    }
    const ecommerceData = JSON.stringify({ type: 'wordpress', productVariations })
    iframe.contentWindow.postMessage(
      { type: 'init', price, currency, accessKey: settings.access_key, ecommerceData },
      '*'
    )
    Object.assign(iframe.style, {
      display: 'block',
      outline: 'none',
      border: 'none',
      width: '100%',
      height: '100%',
      position: 'absolute',
      left: 0,
      top: 0
    })
    const root = iframe.parentElement
    if (root.style.display === 'none') root.style.display = 'block'
    root.style.position = 'relative'
  }

  window.addEventListener('message', ({ data }) => {
    if (data.type === 'customizeme_data') {
      dataInput.value = JSON.stringify(data.data)
    } else if (data.type === 'customizeme_price') {
      priceInput.value = data.price
      priceSpan.textContent = data.price

      if (productVariations && jQueryObject) {
        const variation =
          productVariations.find(el => el.sku === data.sku) || productVariations.find(el => el.sku === 'individual')
        const count = Object.keys(variation).length
        const formData = { count, chosenCount: count, data: variation.attributes }
        const variationForm = jQueryObject.events.change[0].data.variationForm

        variationForm.$attributeFields.each((i, select) => {
          select.value =
            'attribute_' + select.id in formData.data ? formData.data['attribute_' + select.id] : select.value
        })
        variationForm.getChosenAttributes = () => formData
        jQueryObject.events.change[0].handler(jQueryObject.events.change[0])
      }
    }
  })

  if (variationsForm) {
    const elements = Array.from(variationsForm.elements)
    elements.forEach(element => {
      if (element.type === 'select-one') {
        element.addEventListener('change', () => {
          const eventData = { type: 'SET_CUSTOMIZATION_SUGGESTION', args: [element.value] }
          iframe.contentWindow.postMessage({ type: 'call_event', eventData }, '*')
        })
      }
    })
  }

  const tryAddIframe = numbersOfTry => {
    const root =
      numbersOfTry < 10 && (customInjectTo || settings.inject)
        ? document.querySelector(customInjectTo || settings.inject)
        : (root = document.getElementById('customizeme_root'))
    if (root) root.appendChild(iframe)
    else setTimeout(() => tryAddIframe(numbersOfTry + 1), 400)
  }

  window.addEventListener('load', () => tryAddIframe(0))
})()
