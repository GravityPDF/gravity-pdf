import React from 'react'

const TemplateScreenshot = ({ image }) => {
  const className = (image) ? 'theme-screenshot' : 'theme-screenshot blank'

  return (
    <div className={className}>
      {image ? <img src={image} alt=""/> : null}
    </div>
  )
}

TemplateScreenshot.propTypes = {
  image: React.PropTypes.string
}

export default TemplateScreenshot