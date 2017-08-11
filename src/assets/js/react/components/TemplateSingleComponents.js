import PropTypes from 'prop-types';
import React from 'react'

/**
 * Contains stateless React components for our Single Template
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2017, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (C) 2017, Blue Liquid Designs

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
 * @since 4.1
 */
export const CurrentTemplate = ({ isCurrentTemplate, label }) => {
  return (isCurrentTemplate) ? (
    <span className="current-label">{label}</span>
  ) : (
    <span />
  )
}

CurrentTemplate.propTypes = {
  isCurrentTemplate: PropTypes.bool,
  label: PropTypes.string
}

/**
 * React Stateless Component
 *
 * Display the template name and version number
 *
 * @since 4.1
 */
export const Name = ({ name, version, versionLabel }) => (
  <h2 className="theme-name">
    {name}

    <Version version={version} label={versionLabel}/>
  </h2>
)

Name.propTypes = {
  name: PropTypes.string,
  version: PropTypes.string,
  versionLabel: PropTypes.string
}

/**
 * React Stateless Component
 *
 * Display the template version number
 *
 * @since 4.1
 */
export const Version = ({ label, version }) => {
  return (version) ? (
    <span className="theme-version">{label}: {version}</span>
  ) : (
    <span />
  )
}

Version.propTypes = {
  label: PropTypes.string,
  version: PropTypes.string
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
  author: PropTypes.string,
  uri: PropTypes.string
}

/**
 * React Stateless Component
 *
 * Display the template group
 *
 * @since 4.1
 */
export const Group = ({ label, group }) => (
  <p className="theme-author">
    <strong>{label}: {group}</strong>
  </p>
)

Group.propTypes = {
  label: PropTypes.string,
  group: PropTypes.string,
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
  desc: PropTypes.string,
}

/**
 * React Stateless Component
 *
 * Display the template tags
 *
 * @since 4.1
 */
export const Tags = ({ label, tags }) => {
  return (tags) ? (
    <p className="theme-tags">
      <span>{label}:</span> {tags}
    </p>
  ) : (
    <span />
  )
}

Tags.propTypes = {
  label: PropTypes.string,
  tags: PropTypes.string,
}
