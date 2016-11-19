import React from 'react'

/**
 * Contains stateless React components for our Single Template
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2016, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (C) 2016, Blue Liquid Designs

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Found
 */

/**
 * React Stateless Component
 *
 * Display the current template label
 *
 * @TODO Move text to PHP
 *
 * @since 4.1
 */
export const CurrentTemplate = ({ isCurrentTemplate }) => {
  return (isCurrentTemplate) ? (
    <span className="current-label">Current Template</span>
  ) : (
    <span />
  )
}

CurrentTemplate.propTypes = {
  isCurrentTemplate: React.PropTypes.bool
}

/**
 * React Stateless Component
 *
 * Display the template name and version number
 *
 * @since 4.1
 */
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

/**
 * React Stateless Component
 *
 * Display the template version number
 *
 * @since 4.1
 */
export const Version = ({ version }) => {
  return (version) ? (
    <span className="theme-version">Version: {version}</span>
  ) : (
    <span />
  )
}

Version.propTypes = {
  version: React.PropTypes.string
}

/**
 * React Stateless Component
 *
 * Display the template author (and link to website, if any)
 *
 * @since 4.1
 */
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

/**
 * React Stateless Component
 *
 * Display the template group
 *
 * @since 4.1
 */
export const Group = ({ group }) => (
  <p className="theme-author">
    <strong>Group: {group}</strong>
  </p>
)

Group.propTypes = {
  group: React.PropTypes.string,
}

/**
 * React Stateless Component
 *
 * Display the template description
 *
 * @since 4.1
 */
export const Description = ({ desc }) => (
  <p className="theme-description">
    {desc}
  </p>
)

Description.propTypes = {
  desc: React.PropTypes.string,
}

/**
 * React Stateless Component
 *
 * Display the template tags
 *
 * @since 4.1
 */
export const Tags = ({ tags }) => {
  return (tags) ? (
    <p className="theme-tags">
      <span>Tags:</span> {tags}
    </p>
  ) : (
    <span />
  )
}

Tags.propTypes = {
  tags: React.PropTypes.string,
}
