;(function () {
  const thTags = document.getElementsByTagName('th')
  const tagsWithCustomizemeData = Array.from(thTags).filter(th => th.innerText.includes('_customizeme_order_data'))

  tagsWithCustomizemeData.forEach(th => {
    const tr = th.parentNode
    const dataElement = tr.querySelector('p')
    const jsonData = dataElement.innerText.replaceAll('\\', '')
    if (!jsonData) {
      return
    }
    const data = JSON.parse(jsonData)
    const div = tr.parentNode.parentNode.parentNode
    tr.remove()

    const table = document.createElement('table')
    table.style.width = '100%'

    const headRow = document.createElement('tr')
    headRow.style.width = '100%'

    const headCells = ['Part name', 'Material', 'Optional Part']
    headCells.forEach(text => {
      const th = document.createElement('th')
      th.textContent = text
      headRow.appendChild(th)
    })
    table.appendChild(headRow)

    const cellDivStyles = {
      display: 'flex',
      gap: '8px',
      alignItems: 'center'
    }
    const miniatureStyles = {
      display: 'flex',
      justifyContent: 'center',
      alignItems: 'center',
      border: '1px solid #f8f8f8',
      width: '32px',
      height: '32px'
    }

    data.forEach(part => {
      const row = document.createElement('tr')
      const nameCell = document.createElement('td')
      const materialCell = document.createElement('td')
      const materialDiv = document.createElement('div')
      Object.assign(materialDiv.style, cellDivStyles)
      const optionalPartCell = document.createElement('td')
      const optionalPartDiv = document.createElement('div')
      Object.assign(optionalPartDiv.style, cellDivStyles)

      nameCell.textContent = part.displayName || part.name

      materialDiv.textContent = part.materialName || initial
      if (part.materialImage) {
        const materialImage = document.createElement('div')
        Object.assign(materialImage.style, miniatureStyles)
        materialImage.style.backgroundImage = `url(${part.materialImage})`
        materialDiv.prepend(materialImage)
      } else if (part.materialColor) {
        const materialColor = document.createElement('div')
        Object.assign(materialColor.style, miniatureStyles)
        materialColor.style.backgroundColor = '#' + part.materialColor
        materialDiv.prepend(materialColor)
      }

      if (part.optionalPartName) {
        optionalPartDiv.textContent = part.optionalPartName
      }
      if (part.optionalPartImage) {
        const optionalPartImage = document.createElement('img')
        Object.assign(optionalPartImage.style, miniatureStyles)
        optionalPartImage.src = part.optionalPartImage
        optionalPartDiv.prepend(optionalPartImage)
      }

      materialCell.appendChild(materialDiv)
      optionalPartCell.appendChild(optionalPartDiv)

      row.appendChild(nameCell)
      row.appendChild(materialCell)
      row.appendChild(optionalPartCell)
      table.appendChild(row)
    })

    div.appendChild(table)
  })
})()
