import React from 'react'

export const CurrentTemplate = ({ isCurrentTemplate }) => {

  if (!isCurrentTemplate) {
    return false
  }

  return (
    <span className="current-label">Current Template</span>
  )
}

CurrentTemplate.propTypes = {
  isCurrentTemplate: React.PropTypes.bool
}

export const Name = ({ name, version }) => (
  <h2 className="theme-name">
    {name}

    <Version version={version}/>
  </h2>
)

Name.propTypes = {
  name: React.PropTypes.string,
  version: React.PropTypes.string
}

export const Version = ({ version }) => {

  if (!version) {
    return false
  }

  return (
    <span className="theme-version">Version: {version}</span>
  )
}

Version.propTypes = {
  version: React.PropTypes.string
}

export const Author = ({ author, uri }) => {

  if (uri) {
    return (
      <p className="theme-author">
        <a href={uri}>
          {author}
        </a>
      </p>
    )
  } else {
    return (
      <p className="theme-author">
        {author}
      </p>
    )
  }
}

Author.propTypes = {
  author: React.PropTypes.string,
  uri: React.PropTypes.string
}

export const Group = ({ group }) => (
  <p className="theme-author">
    <strong>Group: {group}</strong>
  </p>
)

Group.propTypes = {
  group: React.PropTypes.string,
}

export const Description = ({ desc }) => (
  <p className="theme-description">
    {desc}
  </p>
)

Description.propTypes = {
  desc: React.PropTypes.string,
}

export const Tags = ({ tags }) => {
  if (!tags) {
    return false
  }

  return (
    <p className="theme-tags">
      <span>Tags:</span> {tags}
    </p>
  )
}

Tags.propTypes = {
  tags: React.PropTypes.string,
}
