import React from 'react'

const TemplateScreenshots = ({ image }) => {
  const className = (image) ? 'screenshot' : 'screenshot blank'

  return (
    <div className="theme-screenshots">
      <div className={className}>
        {image ? <img src={image} alt=""/> : null}
      </div>
    </div>
  )
}

TemplateScreenshots.propTypes = {
  image: React.PropTypes.string
}

export default TemplateScreenshots