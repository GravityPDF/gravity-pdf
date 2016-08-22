import React from 'react'

export const TemplateDetails = () => (
  <span className="more-details">Template Details</span>
)

export const Group = ({ group }) => (
  <p className="theme-author">{group}</p>
)

Group.propTypes = {
  group: React.PropTypes.string,
}